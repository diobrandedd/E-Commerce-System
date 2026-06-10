<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/dept_pricing.php';
require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';

/** @return array<string, mixed>|null */
function dept_inventory_fetch_row(PDO $pdo, string $productKey, bool $forUpdate = false): ?array
{
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        return null;
    }
    $deptId = dept_product_dept_id($pdo, $def['dept']);
    $sql = 'SELECT dir.*, d.name AS dept_name
            FROM dept_inventory_rows dir
            INNER JOIN departments d ON d.id = dir.dept_id
            WHERE dir.dept_id = ? AND dir.product_key = ?
            LIMIT 1';
    if ($forUpdate) {
        $sql = str_replace('LIMIT 1', 'LIMIT 1 FOR UPDATE', $sql);
    }
    $st = $pdo->prepare($sql);
    $st->execute([$deptId, $productKey]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return array<string, mixed> */
function dept_inventory_ensure_row(PDO $pdo, int $userId, string $productKey): array
{
    $existing = dept_inventory_fetch_row($pdo, $productKey, true);
    if ($existing !== null) {
        return $existing;
    }

    $def = dept_product_by_key($productKey);
    if ($def === null) {
        throw new RuntimeException('Unknown product.');
    }
    $deptId = dept_product_dept_id($pdo, $def['dept']);
    $ins = $pdo->prepare(
        'INSERT INTO dept_inventory_rows (dept_id, product_key, product_label, current_qty, initial_qty, created_by)
         VALUES (?, ?, ?, 0, 0, ?)'
    );
    $ins->execute([$deptId, $productKey, $def['label'], $userId]);

    $row = dept_inventory_fetch_row($pdo, $productKey, true);
    if ($row === null) {
        throw new RuntimeException('Could not create inventory row.');
    }

    return $row;
}

/**
 * Stock-in: operational inventory + costing stock entry log.
 *
 * PT: qty in bags, supplier required, price from Supplier Master; inventory credited in pouches.
 * Other dept products: qty in pcs, manual unit cost on legacy single stock.
 *
 * @return array<string, mixed>
 */
function dept_inventory_stock_in(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $qty,
    string $arrivalDate,
    ?string $note = null,
    ?int $supplierId = null,
    ?float $unitCost = null
): array {
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        throw new RuntimeException('Unknown product.');
    }
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    $arrivalDate = trim($arrivalDate);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $arrivalDate)) {
        throw new RuntimeException('Invalid arrival date.');
    }

    if (dept_product_uses_supplier_pricing($productKey)) {
        return dept_inventory_stock_in_supplier_priced(
            $pdo,
            $userId,
            $productKey,
            $def,
            $qty,
            $arrivalDate,
            $note,
            $supplierId
        );
    }

    return dept_inventory_stock_in_manual_cost(
        $pdo,
        $userId,
        $productKey,
        $def,
        $qty,
        $arrivalDate,
        $note,
        $unitCost
    );
}

/** @param array<string, mixed> $def */
function dept_inventory_stock_in_supplier_priced(
    PDO $pdo,
    int $userId,
    string $productKey,
    array $def,
    float $qtyBags,
    string $arrivalDate,
    ?string $note,
    ?int $supplierId
): array {
    if ($supplierId === null || $supplierId < 1) {
        throw new RuntimeException('Supplier is required for this product stock-in.');
    }

    $priceRow = dept_pricing_resolve_active_price($pdo, $supplierId, $productKey, $arrivalDate);
    if (!$priceRow) {
        throw new RuntimeException('No supplier price for this date. Set ₱/bag in Supplier Master first.');
    }

    dept_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);
    $leafStockId = dept_pricing_leaf_stock_id($pdo, $supplierId, $productKey);
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);

    $pouchesAdded = round($qtyBags * dept_product_sell_units_per_bag($productKey), 2);
    if ($pouchesAdded <= 0) {
        throw new RuntimeException('Converted pouch quantity must be > 0.');
    }

    $row = dept_inventory_ensure_row($pdo, $userId, $productKey);
    $rowId = (int) $row['id'];
    $batching = $arrivalDate . ' 00:00:00';

    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$pouchesAdded, $pouchesAdded, $def['label'], $note, $rowId]);

    $insEntry = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, NULL, ?)'
    );
    $insEntry->execute([$leafStockId, $deptId, $qtyBags, $batching, $userId]);

    $stQty = $pdo->prepare('SELECT current_qty FROM dept_inventory_rows WHERE id = ?');
    $stQty->execute([$rowId]);

    return [
        'dept_row_id' => $rowId,
        'product_key' => $productKey,
        'qty_added' => $pouchesAdded,
        'bags_received' => $qtyBags,
        'pouches_added' => $pouchesAdded,
        'current_qty' => (float) $stQty->fetchColumn(),
        'stock_id' => $leafStockId,
        'supplier_id' => $supplierId,
        'price_per_bag' => round((float) $priceRow['price_per_unit'], 2),
    ];
}

/** @param array<string, mixed> $def */
function dept_inventory_stock_in_manual_cost(
    PDO $pdo,
    int $userId,
    string $productKey,
    array $def,
    float $qty,
    string $arrivalDate,
    ?string $note,
    ?float $unitCost
): array {
    if ($unitCost === null || $unitCost < 0) {
        throw new RuntimeException('Unit cost is required and cannot be negative.');
    }
    $stockName = (string) ($def['stock_name'] ?? '');
    if ($stockName === '') {
        throw new RuntimeException('No costing stock configured for this product.');
    }

    $batching = $arrivalDate . ' 00:00:00';
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $row = dept_inventory_ensure_row($pdo, $userId, $productKey);
    $rowId = (int) $row['id'];

    $stLock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id IS NULL LIMIT 1 FOR UPDATE');
    $stLock->execute([$deptId, $stockName]);
    $stockRow = $stLock->fetch(PDO::FETCH_ASSOC);
    $stockId = (int) ($stockRow['id'] ?? 0);
    $oldPrice = round((float) ($stockRow['current_price'] ?? 0), 6);
    $newPrice = round($unitCost, 6);

    if ($stockId < 1) {
        $insStock = $pdo->prepare(
            'INSERT INTO stocks (name, dept_id, unit, current_price, supplier_name, supplier_code, created_by)
             VALUES (?, ?, ?, ?, NULL, NULL, ?)'
        );
        $insStock->execute([$stockName, $deptId, (string) $def['unit'], $newPrice, $userId]);
        $stockId = (int) $pdo->lastInsertId();
        $oldPrice = 0.0;
    }

    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$qty, $qty, $def['label'], $note, $rowId]);

    if ($newPrice !== $oldPrice) {
        api_costing_apply_price_change($pdo, $stockId, $oldPrice, $newPrice, $userId);
    }

    $insEntry = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, NULL, ?)'
    );
    $insEntry->execute([$stockId, $deptId, $qty, $batching, $userId]);

    $stQty = $pdo->prepare('SELECT current_qty FROM dept_inventory_rows WHERE id = ?');
    $stQty->execute([$rowId]);

    return [
        'dept_row_id' => $rowId,
        'product_key' => $productKey,
        'qty_added' => $qty,
        'current_qty' => (float) $stQty->fetchColumn(),
        'stock_id' => $stockId,
    ];
}

/** @return array{dept_row_id: int, product_key: string, qty: float, remaining: float} */
function dept_inventory_deduct(PDO $pdo, string $productKey, float $qty): array
{
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }

    $def = dept_product_by_key($productKey);
    if ($def === null) {
        throw new RuntimeException('Unknown product for deduct.');
    }

    $row = dept_inventory_fetch_row($pdo, $productKey, true);
    if ($row === null) {
        throw new RuntimeException('No inventory row for ' . $def['label'] . '. Stock in first.');
    }

    $onHand = (float) $row['current_qty'];
    if ($qty > $onHand + 0.0001) {
        throw new RuntimeException(
            'Not enough ' . $def['label'] . ' on hand (' . $onHand . ' available, need ' . $qty . ').'
        );
    }

    $rowId = (int) $row['id'];
    $upd = $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?');
    $upd->execute([$qty, $rowId, $qty]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Could not deduct ' . $def['label'] . ' inventory.');
    }

    $remaining = $onHand - $qty;

    return [
        'dept_row_id' => $rowId,
        'product_key' => $productKey,
        'qty' => $qty,
        'remaining' => $remaining,
    ];
}

function dept_inventory_resolve_other_item(string $label): ?string
{
    $def = dept_product_resolve_label($label);

    return $def['key'] ?? null;
}

/** @return list<array<string, mixed>> */
function dept_inventory_catalog_snapshot(PDO $pdo, ?string $deptKey = null, ?string $productKey = null): array
{
    $items = [];
    foreach (dept_product_catalog() as $def) {
        if ($deptKey !== null && $deptKey !== '' && $def['dept'] !== $deptKey) {
            continue;
        }
        if ($productKey !== null && $productKey !== '' && $def['key'] !== $productKey) {
            continue;
        }

        $row = dept_inventory_fetch_row($pdo, $def['key']);
        $deptId = dept_product_dept_id($pdo, $def['dept']);

        $stProd = $pdo->prepare(
            'SELECT id, selling_price FROM products WHERE dept_product_key = ? LIMIT 1'
        );
        $stProd->execute([$def['key']]);
        $prod = $stProd->fetch(PDO::FETCH_ASSOC);

        $items[] = [
            'key' => $def['key'],
            'label' => $def['label'],
            'code' => $def['code'],
            'dept' => $def['dept'],
            'dept_id' => $deptId,
            'unit' => $def['inventory_unit'],
            'stock_in_unit' => $def['stock_in_unit'],
            'inventory_unit' => $def['inventory_unit'],
            'uses_supplier_pricing' => $def['uses_supplier_pricing'],
            'tea_bags_per_pouch' => $def['key'] === 'pt' ? PT_TEA_BAGS_PER_POUCH : null,
            'tea_bags_per_bag' => $def['key'] === 'pt' ? dept_pt_tea_bags_per_bag() : null,
            'grams_per_pouch' => $def['key'] === 'brc' ? BRC_GRAMS_PER_POUCH : null,
            'grams_per_bag' => match ($def['key']) {
                'pt' => PT_GRAMS_PER_BAG,
                'brc' => BRC_GRAMS_PER_BAG,
                'jar_cacao' => JAR_CACAO_GRAMS_PER_BAG,
                default => null,
            },
            'grams_per_sell_unit' => $def['key'] === 'jar_cacao' ? JAR_CACAO_GRAMS_PER_JAR : null,
            'pouches_per_bag' => ($def['uses_supplier_pricing'] ?? false)
                ? dept_product_sell_units_per_bag((string) $def['key'])
                : null,
            'sell_units_per_bag' => ($def['uses_supplier_pricing'] ?? false)
                ? dept_product_sell_units_per_bag((string) $def['key'])
                : null,
            'current_qty' => $row !== null ? (float) $row['current_qty'] : 0.0,
            'initial_qty' => $row !== null ? (float) $row['initial_qty'] : 0.0,
            'dept_row_id' => $row !== null ? (int) $row['id'] : null,
            'product_id' => $prod ? (int) $prod['id'] : null,
            'selling_price' => $prod ? round((float) $prod['selling_price'], 2) : null,
            'last_stock_in_at' => $row['last_stock_in_at'] ?? null,
        ];
    }

    return $items;
}
