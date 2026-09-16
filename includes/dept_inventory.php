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
    return dept_raw_stock_in_bags($pdo, $userId, $productKey, $def, $qtyBags, $arrivalDate, $note, $supplierId);
}

function dept_raw_material_slug_to_product_key(string $slug): ?string
{
    $map = [
        'paragis' => 'pt',
        'black-rice' => 'brc',
        'black_rice' => 'brc',
        'cacao' => 'jar_cacao',
        'honey-comb' => 'honey_comb',
        'honey_comb' => 'honey_comb',
    ];

    return $map[trim($slug)] ?? null;
}

function dept_raw_bags_on_hand(PDO $pdo, string $productKey): float
{
    $stockName = dept_pricing_leaf_stock_name($productKey);
    if ($stockName === null) {
        return 0.0;
    }
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        return 0.0;
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $st = $pdo->prepare(
        'SELECT COALESCE(SUM(se.qty), 0)
         FROM stock_entries se
         INNER JOIN stocks s ON s.id = se.stock_id
         WHERE s.dept_id = ? AND s.name = ?'
    );
    $st->execute([$deptId, $stockName]);

    return round((float) $st->fetchColumn(), 4);
}

/** @return list<array<string, mixed>> */
function dept_raw_recent_entries(PDO $pdo, string $productKey, int $limit = 25): array
{
    $stockName = dept_pricing_leaf_stock_name($productKey);
    $def = dept_product_by_key($productKey);
    if ($stockName === null || $def === null) {
        return [];
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $limit = max(1, min(100, $limit));
    $st = $pdo->prepare(
        'SELECT se.id, se.stock_id, se.qty, se.batching_date, se.supplier_batch_no, se.created_at,
                s.supplier_id, sup.first_name, sup.last_name, s.supplier_name, s.supplier_code
         FROM stock_entries se
         INNER JOIN stocks s ON s.id = se.stock_id
         LEFT JOIN suppliers sup ON sup.id = s.supplier_id
         WHERE s.dept_id = ? AND s.name = ?
         ORDER BY se.created_at DESC, se.id DESC
         LIMIT ' . $limit
    );
    $st->execute([$deptId, $stockName]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        $supplierName = null;
        if ($row['first_name'] !== null || $row['last_name'] !== null) {
            $supplierName = supplier_display_name(
                (string) ($row['first_name'] ?? ''),
                (string) ($row['last_name'] ?? '')
            );
        }
        if ($supplierName === null || $supplierName === '') {
            $supplierName = $row['supplier_name'] ?? $row['supplier_code'] ?? null;
        }

        return [
            'id' => (int) $row['id'],
            'stock_id' => (int) $row['stock_id'],
            'qty' => round((float) $row['qty'], 4),
            'batching_date' => (string) $row['batching_date'],
            'supplier_batch_no' => $row['supplier_batch_no'],
            'created_at' => (string) $row['created_at'],
            'supplier_id' => $row['supplier_id'] !== null ? (int) $row['supplier_id'] : null,
            'supplier_name' => $supplierName,
        ];
    }, $rows);
}

/** @return array<string, mixed> */
function dept_raw_material_snapshot(PDO $pdo, string $slug): ?array
{
    $productKey = dept_raw_material_slug_to_product_key($slug);
    if ($productKey === null) {
        return null;
    }
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        return null;
    }
    $finished = dept_inventory_fetch_row($pdo, $productKey);
    $stockName = dept_pricing_leaf_stock_name($productKey);
    $bagsOnHand = dept_raw_bags_on_hand($pdo, $productKey);
    $bagsGross = dept_raw_bags_received_gross($pdo, $productKey);
    $out = [
        'slug' => trim($slug),
        'product_key' => $productKey,
        'title' => match ($productKey) {
            'pt' => 'Dried Paragis',
            'brc' => 'Black Rice',
            'jar_cacao' => 'Cacao Beans',
            'honey_comb' => 'Honey Comb (raw)',
            default => (string) $def['label'],
        },
        'stock_name' => $stockName,
        'unit' => (string) $def['stock_in_unit'],
        'inventory_unit' => (string) $def['inventory_unit'],
        'sell_units_per_bag' => dept_product_sell_units_per_bag($productKey),
        'bags_on_hand' => $bagsOnHand,
        'finished_on_hand' => $finished !== null ? round((float) $finished['current_qty'], 2) : 0.0,
        'finished_label' => (string) $def['label'],
        'entries' => dept_raw_recent_entries($pdo, $productKey, 25),
    ];
    if ($productKey === 'pt') {
        require_once __DIR__ . '/dept_pt_settings.php';
        $bom = dept_pt_bom_snapshot($pdo);
        $gramsPerBag = (float) $bom['grams_per_bag'];
        $gramsRemaining = round($bagsOnHand * $gramsPerBag, 2);
        $gramsCapacity = round($bagsGross * $gramsPerBag, 2);
        $out['sell_units_per_bag'] = $bom['pouches_per_bag'];
        $out['bag_pool'] = [
            'bags_on_hand' => $bagsOnHand,
            'bags_received_gross' => $bagsGross,
            'grams_remaining' => $gramsRemaining,
            'grams_capacity' => $gramsCapacity,
            'grams_per_bag' => $gramsPerBag,
            'grams_per_pouch' => $bom['grams_per_pouch'],
            'grams_per_tea_bag' => $bom['grams_per_tea_bag'],
            'tea_bags_per_pouch' => $bom['tea_bags_per_pouch'],
            'pouches_per_bag' => $bom['pouches_per_bag'],
            'overdrawn' => $bagsOnHand < -0.0001,
        ];
    } elseif ($productKey === 'brc') {
        require_once __DIR__ . '/dept_brc_settings.php';
        $bom = dept_brc_bom_snapshot($pdo);
        $gramsPerBag = (float) $bom['grams_per_bag'];
        $gramsRemaining = round($bagsOnHand * $gramsPerBag, 2);
        $gramsCapacity = round($bagsGross * $gramsPerBag, 2);
        $out['sell_units_per_bag'] = $bom['pouches_per_bag'];
        $out['bag_pool'] = [
            'bags_on_hand' => $bagsOnHand,
            'bags_received_gross' => $bagsGross,
            'grams_remaining' => $gramsRemaining,
            'grams_capacity' => $gramsCapacity,
            'grams_per_bag' => $gramsPerBag,
            'grams_per_pouch' => $bom['grams_per_pouch'],
            'grams_per_tea_bag' => null,
            'tea_bags_per_pouch' => null,
            'pouches_per_bag' => $bom['pouches_per_bag'],
            'overdrawn' => $bagsOnHand < -0.0001,
        ];
    } elseif ($productKey === 'jar_cacao') {
        require_once __DIR__ . '/dept_jar_cacao_settings.php';
        $bom = dept_jar_cacao_bom_snapshot($pdo);
        $gramsPerBag = (float) $bom['grams_per_bag'];
        $gramsRemaining = round($bagsOnHand * $gramsPerBag, 2);
        $gramsCapacity = round($bagsGross * $gramsPerBag, 2);
        $out['sell_units_per_bag'] = $bom['jars_per_bag'];
        $out['bag_pool'] = [
            'bags_on_hand' => $bagsOnHand,
            'bags_received_gross' => $bagsGross,
            'grams_remaining' => $gramsRemaining,
            'grams_capacity' => $gramsCapacity,
            'grams_per_bag' => $gramsPerBag,
            'grams_per_pouch' => $bom['grams_per_jar'],
            'grams_per_tea_bag' => null,
            'tea_bags_per_pouch' => null,
            'pouches_per_bag' => $bom['jars_per_bag'],
            'overdrawn' => $bagsOnHand < -0.0001,
        ];
    }

    return $out;
}

/**
 * Receive supplier bags into raw leaf stock only (no finished credit).
 *
 * @param array<string, mixed> $def
 * @return array<string, mixed>
 */
function dept_raw_stock_in_bags(
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
    if ($qtyBags <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }

    $priceRow = dept_pricing_resolve_active_price($pdo, $supplierId, $productKey, $arrivalDate);
    if (!$priceRow) {
        throw new RuntimeException('No supplier price for this date. Set ₱/' . (string) $def['stock_in_unit'] . ' in Supplier Master first.');
    }

    dept_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);
    $leafStockId = dept_pricing_leaf_stock_id($pdo, $supplierId, $productKey);
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $batching = $arrivalDate . ' 00:00:00';

    $insEntry = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, NULL, ?)'
    );
    $insEntry->execute([$leafStockId, $deptId, $qtyBags, $batching, $userId]);

    return [
        'product_key' => $productKey,
        'bags_received' => $qtyBags,
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),
        'stock_id' => $leafStockId,
        'supplier_id' => $supplierId,
        'price_per_bag' => round((float) $priceRow['price_per_unit'], 2),
    ];
}

/**
 * FIFO deduct raw bags across all supplier leaf stocks for a product.
 *
 * @return list<array{stock_id: int, qty: float}>
 */
function dept_raw_deduct_bags_fifo(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $qtyBags,
    string $entryLabel
): array {
    if ($qtyBags <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    $stockName = dept_pricing_leaf_stock_name($productKey);
    $def = dept_product_by_key($productKey);
    if ($stockName === null || $def === null) {
        throw new RuntimeException('Unknown product.');
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);

    $st = $pdo->prepare(
        'SELECT s.id,
                (SELECT COALESCE(SUM(se.qty), 0) FROM stock_entries se WHERE se.stock_id = s.id) AS balance,
                (SELECT MIN(se.batching_date) FROM stock_entries se WHERE se.stock_id = s.id AND se.qty > 0) AS first_in
         FROM stocks s
         WHERE s.dept_id = ? AND s.name = ?
         HAVING balance > 0.0001
         ORDER BY first_in ASC, s.id ASC'
    );
    $st->execute([$deptId, $stockName]);
    $stocks = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $remaining = $qtyBags;
    $allocations = [];
    $insEntry = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $batching = date('Y-m-d H:i:s');
    $label = substr(trim($entryLabel), 0, 120);

    foreach ($stocks as $stock) {
        if ($remaining <= 0.0001) {
            break;
        }
        $balance = round((float) $stock['balance'], 4);
        if ($balance <= 0) {
            continue;
        }
        $take = min($remaining, $balance);
        $stockId = (int) $stock['id'];
        $insEntry->execute([$stockId, $deptId, -$take, $batching, $label !== '' ? $label : null, $userId]);
        $allocations[] = ['stock_id' => $stockId, 'qty' => $take];
        $remaining = round($remaining - $take, 4);
    }

    if ($remaining > 0.0001) {
        $onHand = dept_raw_bags_on_hand($pdo, $productKey);
        throw new RuntimeException(
            'Not enough raw bags on hand (' . round($onHand, 2) . ' available, need ' . round($qtyBags, 2) . ').'
        );
    }

    return $allocations;
}

/**
 * Deduct raw bags FIFO; if short, post the remainder as an overdraft (negative pool allowed).
 *
 * @return array{
 *   allocations: list<array{stock_id: int, qty: float}>,
 *   bags_deducted: float,
 *   bags_on_hand: float,
 *   warned: bool,
 *   shortfall_bags: float
 * }
 */
function dept_raw_deduct_bags_allow_negative(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $qtyBags,
    string $entryLabel
): array {
    if ($qtyBags <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    $stockName = dept_pricing_leaf_stock_name($productKey);
    $def = dept_product_by_key($productKey);
    if ($stockName === null || $def === null) {
        throw new RuntimeException('Unknown product.');
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $before = dept_raw_bags_on_hand($pdo, $productKey);
    $warned = $before + 1e-9 < $qtyBags;
    $shortfall = $warned ? round(max(0, $qtyBags - max(0, $before)), 6) : 0.0;

    $st = $pdo->prepare(
        'SELECT s.id,
                (SELECT COALESCE(SUM(se.qty), 0) FROM stock_entries se WHERE se.stock_id = s.id) AS balance,
                (SELECT MIN(se.batching_date) FROM stock_entries se WHERE se.stock_id = s.id AND se.qty > 0) AS first_in
         FROM stocks s
         WHERE s.dept_id = ? AND s.name = ?
         ORDER BY first_in IS NULL ASC, first_in ASC, s.id ASC'
    );
    $st->execute([$deptId, $stockName]);
    $stocks = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($stocks === []) {
        throw new RuntimeException(
            'No raw leaf stock row exists yet. Stock in raw bags before scanning pouches.'
        );
    }

    $remaining = $qtyBags;
    $allocations = [];
    $insEntry = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $batching = date('Y-m-d H:i:s');
    $label = substr(trim($entryLabel), 0, 120);
    $label = $label !== '' ? $label : 'pouch_scan';

    foreach ($stocks as $stock) {
        if ($remaining <= 0.0001) {
            break;
        }
        $balance = round((float) $stock['balance'], 6);
        if ($balance <= 0.0001) {
            continue;
        }
        $take = min($remaining, $balance);
        $stockId = (int) $stock['id'];
        $insEntry->execute([$stockId, $deptId, -$take, $batching, $label, $userId]);
        $allocations[] = ['stock_id' => $stockId, 'qty' => $take];
        $remaining = round($remaining - $take, 6);
    }

    if ($remaining > 0.0001) {
        // Overdraft on the newest leaf stock (or first available).
        $overdraftStockId = (int) $stocks[count($stocks) - 1]['id'];
        $insEntry->execute([$overdraftStockId, $deptId, -$remaining, $batching, $label . ':overdraft', $userId]);
        $allocations[] = ['stock_id' => $overdraftStockId, 'qty' => $remaining];
        $remaining = 0.0;
    }

    return [
        'allocations' => $allocations,
        'bags_deducted' => $qtyBags,
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),
        'warned' => $warned,
        'shortfall_bags' => $shortfall,
    ];
}

/**
 * Gross positive bag receipts (capacity) for progress UI.
 */
function dept_raw_bags_received_gross(PDO $pdo, string $productKey): float
{
    $stockName = dept_pricing_leaf_stock_name($productKey);
    $def = dept_product_by_key($productKey);
    if ($stockName === null || $def === null) {
        return 0.0;
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $st = $pdo->prepare(
        'SELECT COALESCE(SUM(se.qty), 0)
         FROM stock_entries se
         INNER JOIN stocks s ON s.id = se.stock_id
         WHERE s.dept_id = ? AND s.name = ? AND se.qty > 0'
    );
    $st->execute([$deptId, $stockName]);

    return round((float) $st->fetchColumn(), 4);
}

/** @return array<string, mixed> */
function dept_raw_stock_out_bags(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $qtyBags,
    ?string $reason = null
): array {
    $label = $reason !== null && trim($reason) !== '' ? 'stock_out: ' . trim($reason) : 'stock_out';
    dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $qtyBags, $label);

    return [
        'product_key' => $productKey,
        'bags_out' => $qtyBags,
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),
    ];
}

/** @return array<string, mixed> */
function dept_inventory_produce_from_bags(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $qtyBags,
    ?string $note = null
): array {
    $def = dept_product_by_key($productKey);
    if ($def === null || !($def['uses_supplier_pricing'] ?? false)) {
        throw new RuntimeException('This product does not use bag-based production.');
    }
    if ($qtyBags <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }

    $sellUnitsAdded = round($qtyBags * dept_product_sell_units_per_bag($productKey), 2);
    if ($sellUnitsAdded <= 0) {
        throw new RuntimeException('Converted sellable quantity must be > 0.');
    }

    $label = $note !== null && trim($note) !== '' ? 'produce: ' . trim($note) : 'produce';
    dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $qtyBags, $label);

    $row = dept_inventory_ensure_row($pdo, $userId, $productKey);
    $rowId = (int) $row['id'];
    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$sellUnitsAdded, $sellUnitsAdded, $def['label'], $note, $rowId]);

    $stQty = $pdo->prepare('SELECT current_qty FROM dept_inventory_rows WHERE id = ?');
    $stQty->execute([$rowId]);

    return [
        'product_key' => $productKey,
        'bags_used' => $qtyBags,
        'sell_units_added' => $sellUnitsAdded,
        'inventory_unit' => (string) $def['inventory_unit'],
        'finished_on_hand' => (float) $stQty->fetchColumn(),
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),
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
                'honey_comb' => HONEY_COMB_GRAMS_PER_BAG,
                default => null,
            },
            'grams_per_sell_unit' => match ($def['key']) {
                'jar_cacao' => JAR_CACAO_GRAMS_PER_JAR,
                'honey_comb' => HONEY_COMB_GRAMS_PER_JAR,
                default => null,
            },
            'tablets_per_jar' => $def['key'] === 'jar_cacao' ? JAR_CACAO_TABLETS_PER_JAR : null,
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

/**
 * Grams-per-kg (API field grams_per_bag) for leaf pool products. Default 1000.
 */
function dept_raw_pool_grams_per_bag(PDO $pdo, string $productKey): float
{
    if ($productKey === 'pt') {
        require_once __DIR__ . '/dept_pt_settings.php';
        $bom = dept_pt_bom_snapshot($pdo);

        return max(0.0001, (float) $bom['grams_per_bag']);
    }
    if ($productKey === 'brc') {
        require_once __DIR__ . '/dept_brc_settings.php';
        $bom = dept_brc_bom_snapshot($pdo);

        return max(0.0001, (float) $bom['grams_per_bag']);
    }
    if ($productKey === 'jar_cacao') {
        require_once __DIR__ . '/dept_jar_cacao_settings.php';
        $bom = dept_jar_cacao_bom_snapshot($pdo);

        return max(0.0001, (float) $bom['grams_per_bag']);
    }

    return 1000.0;
}

/**
 * Executive / monthly count: set raw leaf on-hand (kg; API field bags_on_hand) via balancing stock_entries row.
 *
 * @return array{product_key: string, bags_on_hand: float, previous: float, entry_id: int|null, grams_remaining?: float, grams_per_bag?: float}
 */
function dept_raw_set_bags_on_hand(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $newBags,
    ?string $note = null,
    string $logSource = 'monthly_inventory'
): array {
    if ($newBags < 0) {
        throw new RuntimeException('On-hand quantity cannot be negative.');
    }
    $def = dept_product_by_key($productKey);
    $stockName = dept_pricing_leaf_stock_name($productKey);
    if ($def === null || $stockName === null) {
        throw new RuntimeException('Unknown product.');
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $previous = dept_raw_bags_on_hand($pdo, $productKey);
    $delta = round($newBags - $previous, 6);
    if (abs($delta) < 0.0000001) {
        $gramsPerBag = dept_raw_pool_grams_per_bag($pdo, $productKey);

        return [
            'product_key' => $productKey,
            'bags_on_hand' => $previous,
            'previous' => $previous,
            'entry_id' => null,
            'grams_remaining' => round($previous * $gramsPerBag, 2),
            'grams_per_bag' => $gramsPerBag,
        ];
    }

    $st = $pdo->prepare(
        'SELECT s.id,
                (SELECT COALESCE(SUM(se.qty), 0) FROM stock_entries se WHERE se.stock_id = s.id) AS balance
         FROM stocks s
         WHERE s.dept_id = ? AND s.name = ?
         ORDER BY balance DESC, s.id ASC
         LIMIT 1'
    );
    $st->execute([$deptId, $stockName]);
    $stock = $st->fetch(PDO::FETCH_ASSOC);
    if (!$stock) {
        throw new RuntimeException('No leaf stock for this product. Stock-in at least once first, or set supplier prices.');
    }
    $stockId = (int) $stock['id'];
    $defaultNote = $logSource === 'executive_edit' ? 'Executive pool correction' : 'Monthly inventory count';
    $auditNote = $note !== null && trim($note) !== '' ? trim($note) : $defaultNote;
    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        $deptId,
        $delta,
        date('Y-m-d H:i:s'),
        substr('count_correction: ' . $auditNote, 0, 120),
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();

    require_once __DIR__ . '/inventory_edit_log.php';
    inventory_edit_log_write(
        $pdo,
        $userId,
        'dept_raw_bags',
        $stockId,
        $productKey . ':bags_on_hand',
        (string) $previous,
        (string) $newBags,
        $logSource
    );

    $gramsPerBag = dept_raw_pool_grams_per_bag($pdo, $productKey);

    return [
        'product_key' => $productKey,
        'bags_on_hand' => round($newBags, 6),
        'previous' => $previous,
        'entry_id' => $entryId,
        'grams_remaining' => round($newBags * $gramsPerBag, 2),
        'grams_per_bag' => $gramsPerBag,
    ];
}

/**
 * Set leaf pool by target grams remaining (converts to kg using Settings grams_per_bag).
 *
 * @return array{product_key: string, bags_on_hand: float, previous: float, entry_id: int|null, grams_remaining: float, grams_per_bag: float}
 */
function dept_raw_set_grams_on_hand(
    PDO $pdo,
    int $userId,
    string $productKey,
    float $newGrams,
    ?string $note = null,
    string $logSource = 'executive_edit'
): array {
    if (!in_array($productKey, ['pt', 'brc', 'jar_cacao'], true)) {
        throw new RuntimeException('Gram edits are only for Paragis, Black Rice, and Jar Cacao raw pools.');
    }
    if ($newGrams < 0) {
        throw new RuntimeException('Grams on-hand cannot be negative.');
    }
    $gramsPerBag = dept_raw_pool_grams_per_bag($pdo, $productKey);
    $newKg = round($newGrams / $gramsPerBag, 6);

    return dept_raw_set_bags_on_hand($pdo, $userId, $productKey, $newKg, $note, $logSource);
}

