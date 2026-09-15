<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/suppliers.php';
require_once __DIR__ . '/honey_inventory.php';
require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';

/** @return array<string, mixed>|null */
function dept_pricing_resolve_active_price(PDO $pdo, int $supplierId, string $productKey, string $arrivalDate): ?array
{
    $productKey = strtolower(trim($productKey));
    if ($supplierId < 1 || !dept_product_uses_supplier_pricing($productKey)) {
        return null;
    }

    $st = $pdo->prepare(
        'SELECT id, supplier_id, product_key, price_per_unit, effective_from
         FROM supplier_dept_prices
         WHERE supplier_id = ? AND product_key = ? AND effective_from <= ?
         ORDER BY effective_from DESC, id DESC
         LIMIT 1'
    );
    $st->execute([$supplierId, $productKey, $arrivalDate]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function dept_pricing_stocks_has_supplier_id(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $st->execute(['stocks', 'supplier_id']);
    $cache = (bool) $st->fetchColumn();

    return $cache;
}

function dept_pricing_leaf_stock_name(string $productKey): ?string
{
    $def = dept_product_by_key($productKey);

    return $def['leaf_stock_name'] ?? null;
}

/** @return list<array<string, mixed>> */
function dept_pricing_list_supplier_prices(PDO $pdo, ?int $supplierId = null, ?string $productKey = null): array
{
    $sql = 'SELECT sdp.id, sdp.supplier_id, sdp.product_key, sdp.price_per_unit, sdp.effective_from,
                   s.first_name, s.last_name, s.category
            FROM supplier_dept_prices sdp
            INNER JOIN suppliers s ON s.id = sdp.supplier_id
            WHERE s.category IN ("paragis", "black_rice", "cacao", "honey_comb")';
    $params = [];
    if ($supplierId !== null && $supplierId > 0) {
        $sql .= ' AND sdp.supplier_id = ?';
        $params[] = $supplierId;
    }
    if ($productKey !== null && $productKey !== '') {
        $sql .= ' AND sdp.product_key = ?';
        $params[] = strtolower(trim($productKey));
    }
    $sql .= ' ORDER BY s.last_name ASC, s.first_name ASC, sdp.product_key ASC, sdp.effective_from DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @param list<array{product_key: string, price_per_unit: float|string, effective_from?: string}> $rows
 * @return list<array<string, mixed>>
 */
function dept_pricing_save_supplier_prices(PDO $pdo, int $supplierId, array $rows, string $effectiveFrom, int $userId): array
{
    if ($supplierId < 1) {
        throw new RuntimeException('Supplier is required.');
    }
    $supplier = supplier_fetch($pdo, $supplierId);
    if (!$supplier) {
        throw new RuntimeException('Supplier not found.');
    }
    $category = (string) $supplier['category'];
    if ($effectiveFrom === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveFrom)) {
        throw new RuntimeException('Valid effective date is required.');
    }

    $saved = [];
    $ins = $pdo->prepare(
        'INSERT INTO supplier_dept_prices (supplier_id, product_key, price_per_unit, effective_from, created_by)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($rows as $row) {
        $productKey = strtolower(trim((string) ($row['product_key'] ?? '')));
        if (!dept_product_uses_supplier_pricing($productKey)) {
            continue;
        }
        $expectedCategory = dept_pricing_supplier_category_for_product($productKey);
        if ($expectedCategory === null || $category !== $expectedCategory) {
            throw new RuntimeException('Supplier category does not match product.');
        }
        $raw = trim((string) ($row['price_per_unit'] ?? ''));
        if ($raw === '') {
            continue;
        }
        if (!is_numeric($raw) || (float) $raw < 0) {
            throw new RuntimeException('Price per unit must be 0 or greater.');
        }
        $price = round((float) $raw, 2);
        $eff = trim((string) ($row['effective_from'] ?? ''));
        $effDate = $eff !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $eff) ? $eff : $effectiveFrom;

        $ins->execute([$supplierId, $productKey, $price, $effDate, $userId]);
        $saved[] = [
            'id' => (int) $pdo->lastInsertId(),
            'supplier_id' => $supplierId,
            'product_key' => $productKey,
            'price_per_unit' => $price,
            'effective_from' => $effDate,
        ];
    }

    if (!$saved) {
        throw new RuntimeException('Enter at least one product price.');
    }

    dept_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);

    return $saved;
}

/**
 * Upsert per-supplier leaf costing stocks from latest supplier_dept_prices.
 *
 * @return list<string> product keys updated
 */
function dept_pricing_upsert_supplier_costing_stocks(PDO $pdo, int $supplierId, int $userId): array
{
    if ($supplierId < 1 || !dept_pricing_stocks_has_supplier_id($pdo)) {
        return [];
    }
    $supplier = supplier_fetch($pdo, $supplierId);
    if (!$supplier) {
        return [];
    }

    $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
    $supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));
    $asOf = date('Y-m-d');
    $updated = [];

    foreach (dept_product_catalog() as $def) {
        if (!($def['uses_supplier_pricing'] ?? false)) {
            continue;
        }
        $productKey = (string) $def['key'];
        $expectedCategory = dept_pricing_supplier_category_for_product($productKey);
        if ($expectedCategory === null || (string) $supplier['category'] !== $expectedCategory) {
            continue;
        }
        $stockName = dept_pricing_leaf_stock_name($productKey);
        if ($stockName === null || $stockName === '') {
            continue;
        }

        $priceRow = dept_pricing_resolve_active_price($pdo, $supplierId, $productKey, $asOf);
        if (!$priceRow) {
            continue;
        }
        $pricePerBag = round((float) $priceRow['price_per_unit'], 6);
        $deptId = dept_product_dept_id($pdo, (string) $def['dept']);

        $stStock = $pdo->prepare(
            'SELECT id, current_price FROM stocks
             WHERE dept_id = ? AND name = ? AND supplier_id = ? LIMIT 1'
        );
        $stStock->execute([$deptId, $stockName, $supplierId]);
        $stock = $stStock->fetch(PDO::FETCH_ASSOC);

        if (!$stock) {
            $ins = $pdo->prepare(
                'INSERT INTO stocks (name, dept_id, supplier_id, unit, current_price, supplier_name, supplier_code, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $stockName,
                $deptId,
                $supplierId,
                (string) $def['stock_in_unit'],
                $pricePerBag,
                $supplierName,
                $supplierCode,
                $userId,
            ]);
            $stockId = (int) $pdo->lastInsertId();
            dept_pricing_recalc_products_using_stock($pdo, $stockId, 0.0, $pricePerBag, $userId);
            $updated[] = $productKey;
            continue;
        }

        $stockId = (int) $stock['id'];
        $oldPrice = round((float) ($stock['current_price'] ?? 0), 6);
        $updMeta = $pdo->prepare('UPDATE stocks SET supplier_name = ?, supplier_code = ? WHERE id = ?');
        $updMeta->execute([$supplierName, $supplierCode, $stockId]);

        if (abs($oldPrice - $pricePerBag) >= 0.0001) {
            api_costing_apply_price_change($pdo, $stockId, $oldPrice, $pricePerBag, $userId);
            $updated[] = $productKey;
        }
    }

    return $updated;
}

function dept_pricing_recalc_products_using_stock(PDO $pdo, int $stockId, float $oldPrice, float $newPrice, int $userId): void
{
    if (abs($oldPrice - $newPrice) < 0.0001) {
        return;
    }
    api_costing_apply_price_change($pdo, $stockId, $oldPrice, $newPrice, $userId);
}

function dept_pricing_leaf_stock_id(PDO $pdo, int $supplierId, string $productKey): int
{
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        throw new RuntimeException('Unknown product.');
    }
    $stockName = dept_pricing_leaf_stock_name($productKey);
    if ($stockName === null) {
        throw new RuntimeException('No leaf stock for this product.');
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $st = $pdo->prepare(
        'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id = ? LIMIT 1'
    );
    $st->execute([$deptId, $stockName, $supplierId]);
    $id = (int) $st->fetchColumn();
    if ($id < 1) {
        throw new RuntimeException(
            'Leaf costing stock not found for supplier. Save ₱/' . (string) ($def['stock_in_unit'] ?? 'bag') . ' in Supplier Master first.'
        );
    }

    return $id;
}
