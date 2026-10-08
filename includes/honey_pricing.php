<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/honey_volume.php';
require_once __DIR__ . '/pyxacct_ap_sync.php';
require_once __DIR__ . '/../api/v1/_costing_helpers.php';

const HONEY_PRICING_VARIANTS = ['light', 'amber', 'dark'];

function honey_pricing_variant_for_stock(string $variant): ?string
{
    $v = strtolower(trim($variant));
    if (!in_array($v, HONEY_PRICING_VARIANTS, true)) {
        return null;
    }

    return $v;
}

function honey_pricing_stock_name(string $variant): string
{
    return 'Raw Honey — ' . ucfirst($variant);
}

/** @return array<string, mixed>|null */
function honey_pricing_resolve_active_price(PDO $pdo, int $supplierId, string $variant, string $arrivalDate): ?array
{
    $variant = honey_pricing_variant_for_stock($variant);
    if ($variant === null || $supplierId < 1) {
        return null;
    }

    $st = $pdo->prepare(
        'SELECT id, supplier_id, variant, price_per_gallon, effective_from
         FROM supplier_honey_prices
         WHERE supplier_id = ? AND variant = ? AND effective_from <= ?
         ORDER BY effective_from DESC, id DESC
         LIMIT 1'
    );
    $st->execute([$supplierId, $variant, $arrivalDate]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function honey_pricing_apply_row_cost(PDO $pdo, int $warehouseRowId, ?float $unitCost, ?int $supplierPriceId, bool $override): void
{
    $upd = $pdo->prepare(
        'UPDATE honey_warehouse_rows
         SET unit_cost_per_gallon = ?, supplier_price_id = ?, cost_override = ?
         WHERE id = ?'
    );
    $upd->execute([
        $unitCost !== null ? round($unitCost, 2) : null,
        $supplierPriceId,
        $override ? 1 : 0,
        $warehouseRowId,
    ]);

    // Auto-create / update PyxAcct AP when a peso unit cost is set
    if ($unitCost !== null && $unitCost > 0) {
        pyxacct_sync_honey_warehouse_payable($pdo, $warehouseRowId);
    }
}

/**
 * After stock-in or manual price set: snapshot cost from supplier price list when not overridden.
 *
 * @return array{priced: bool, unit_cost_per_gallon: ?float, supplier_price_id: ?int}
 */
function honey_pricing_snapshot_warehouse_row(PDO $pdo, int $warehouseRowId): array
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }
    if ((int) ($wh['cost_override'] ?? 0) === 1) {
        $cost = $wh['unit_cost_per_gallon'] !== null ? round((float) $wh['unit_cost_per_gallon'], 2) : null;

        return [
            'priced' => $cost !== null && $cost >= 0,
            'unit_cost_per_gallon' => $cost,
            'supplier_price_id' => $wh['supplier_price_id'] !== null ? (int) $wh['supplier_price_id'] : null,
        ];
    }

    $supplierId = (int) ($wh['supplier_id'] ?? 0);
    $variant = (string) ($wh['variant'] ?? '');
    $arrival = (string) ($wh['arrival_date'] ?? date('Y-m-d'));
    $priceRow = honey_pricing_resolve_active_price($pdo, $supplierId, $variant, $arrival);

    if (!$priceRow) {
        honey_pricing_apply_row_cost($pdo, $warehouseRowId, null, null, false);

        return ['priced' => false, 'unit_cost_per_gallon' => null, 'supplier_price_id' => null];
    }

    $unit = round((float) $priceRow['price_per_gallon'], 2);
    $priceId = (int) $priceRow['id'];
    honey_pricing_apply_row_cost($pdo, $warehouseRowId, $unit, $priceId, false);

    return ['priced' => true, 'unit_cost_per_gallon' => $unit, 'supplier_price_id' => $priceId];
}

function honey_pricing_stocks_has_supplier_id(PDO $pdo): bool
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

/**
 * Upsert per-supplier costing stocks from latest supplier_honey_prices (₱/gal → ₱/L).
 *
 * @return list<string> variant keys updated
 */
function honey_pricing_upsert_supplier_costing_stocks(PDO $pdo, int $supplierId, int $userId): array
{
    if ($supplierId < 1 || !honey_pricing_stocks_has_supplier_id($pdo)) {
        return [];
    }
    $supplier = supplier_fetch($pdo, $supplierId);
    if (!$supplier || (string) $supplier['category'] !== 'honey') {
        return [];
    }

    $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
    $supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));
    $deptId = honey_pos_resolve_honey_dept_id($pdo);
    $asOf = date('Y-m-d');
    $updated = [];

    foreach (HONEY_PRICING_VARIANTS as $variant) {
        $priceRow = honey_pricing_resolve_active_price($pdo, $supplierId, $variant, $asOf);
        if (!$priceRow) {
            continue;
        }
        $pricePerLiter = round(honey_price_per_liter_from_gallon((float) $priceRow['price_per_gallon']), 6);
        $stockName = honey_pricing_stock_name($variant);

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
                'liter',
                $pricePerLiter,
                $supplierName,
                $supplierCode,
                $userId,
            ]);
            $stockId = (int) $pdo->lastInsertId();
            honey_pricing_recalc_products_using_stock($pdo, $stockId, 0.0, $pricePerLiter, $userId);
            $updated[] = $variant;
            continue;
        }

        $stockId = (int) $stock['id'];
        $oldPrice = round((float) ($stock['current_price'] ?? 0), 6);
        $updMeta = $pdo->prepare(
            'UPDATE stocks SET supplier_name = ?, supplier_code = ? WHERE id = ?'
        );
        $updMeta->execute([$supplierName, $supplierCode, $stockId]);

        if (abs($oldPrice - $pricePerLiter) >= 0.0001) {
            api_costing_apply_price_change($pdo, $stockId, $oldPrice, $pricePerLiter, $userId);
            $updated[] = $variant;
        }
    }

    return $updated;
}

/** @deprecated Legacy WAC on shared stocks; no longer called from warehouse flow. */
function honey_pricing_sync_variant_stock_cost(PDO $pdo, string $variant, int $userId): ?float
{
    return null;
}

function honey_pricing_recalc_products_using_stock(PDO $pdo, int $stockId, float $oldPrice, float $newPrice, int $userId): void
{
    if (abs($oldPrice - $newPrice) < 0.0001) {
        return;
    }
    api_costing_apply_price_change($pdo, $stockId, $oldPrice, $newPrice, $userId);
}

/** Refresh all honey suppliers' costing stocks from latest list prices. */
function honey_pricing_sync_all_variant_stocks(PDO $pdo, int $userId): array
{
    $synced = [];
    $st = $pdo->query('SELECT id FROM suppliers WHERE category = "honey" AND is_active = 1');
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $supplierId) {
        $variants = honey_pricing_upsert_supplier_costing_stocks($pdo, (int) $supplierId, $userId);
        if ($variants !== []) {
            $synced[(int) $supplierId] = $variants;
        }
    }

    return $synced;
}

/** @return list<array<string, mixed>> */
function honey_pricing_list_supplier_prices(PDO $pdo, ?int $supplierId = null): array
{
    $sql = 'SELECT shp.id, shp.supplier_id, shp.variant, shp.price_per_gallon, shp.effective_from,
                   s.first_name, s.last_name
            FROM supplier_honey_prices shp
            INNER JOIN suppliers s ON s.id = shp.supplier_id
            WHERE s.category = "honey"';
    $params = [];
    if ($supplierId !== null && $supplierId > 0) {
        $sql .= ' AND shp.supplier_id = ?';
        $params[] = $supplierId;
    }
    $sql .= ' ORDER BY s.last_name ASC, s.first_name ASC, shp.variant ASC, shp.effective_from DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @param list<array{variant: string, price_per_gallon: float|string, effective_from?: string}> $rows
 * @return list<array<string, mixed>>
 */
function honey_pricing_save_supplier_prices(PDO $pdo, int $supplierId, array $rows, string $effectiveFrom, int $userId): array
{
    if ($supplierId < 1) {
        throw new RuntimeException('Supplier is required.');
    }
    $supplier = supplier_fetch($pdo, $supplierId);
    if (!$supplier || (string) $supplier['category'] !== 'honey') {
        throw new RuntimeException('Honey supplier not found.');
    }
    if ($effectiveFrom === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveFrom)) {
        throw new RuntimeException('Valid effective date is required.');
    }

    $saved = [];
    $ins = $pdo->prepare(
        'INSERT INTO supplier_honey_prices (supplier_id, variant, price_per_gallon, effective_from, created_by)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($rows as $row) {
        $variant = honey_pricing_variant_for_stock((string) ($row['variant'] ?? ''));
        if ($variant === null) {
            continue;
        }
        $raw = trim((string) ($row['price_per_gallon'] ?? ''));
        if ($raw === '') {
            continue;
        }
        if (!is_numeric($raw) || (float) $raw < 0) {
            throw new RuntimeException('Price per gallon must be 0 or greater.');
        }
        $price = round((float) $raw, 2);
        $eff = trim((string) ($row['effective_from'] ?? ''));
        $effDate = $eff !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $eff) ? $eff : $effectiveFrom;

        $ins->execute([$supplierId, $variant, $price, $effDate, $userId]);
        $saved[] = [
            'id' => (int) $pdo->lastInsertId(),
            'supplier_id' => $supplierId,
            'variant' => $variant,
            'price_per_gallon' => $price,
            'effective_from' => $effDate,
        ];
    }

    if (!$saved) {
        throw new RuntimeException('Enter at least one variant price.');
    }

    honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);

    return $saved;
}

/** Re-apply supplier price list to warehouse rows (skips cost_override). */
function honey_pricing_reprice_pending_for_supplier(PDO $pdo, int $supplierId, int $userId): int
{
    $st = $pdo->prepare(
        'SELECT id FROM honey_warehouse_rows
         WHERE supplier_id = ? AND cost_override = 0'
    );
    $st->execute([$supplierId]);
    $count = 0;
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $wid) {
        $result = honey_pricing_snapshot_warehouse_row($pdo, (int) $wid);
        if ($result['priced']) {
            $count++;
        }
    }
    honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);

    return $count;
}

/** @return list<array<string, mixed>> */
function honey_pricing_pending_warehouse_rows(PDO $pdo, int $limit = 100): array
{
    $st = $pdo->prepare(
        'SELECT w.id, w.supplier_id, w.supplier_name, w.supplier_code, w.arrival_date, w.variant,
                w.current_qty, w.initial_qty, w.created_at
         FROM honey_warehouse_rows w
         WHERE w.unit_cost_per_gallon IS NULL
         ORDER BY w.arrival_date DESC, w.id DESC
         LIMIT ' . max(1, min(500, $limit))
    );
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function honey_pricing_set_warehouse_row_cost(PDO $pdo, int $warehouseRowId, float $unitCost, int $userId): array
{
    if ($unitCost < 0) {
        throw new RuntimeException('Unit cost cannot be negative.');
    }
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }
    $unitCost = round($unitCost, 2);
    honey_pricing_apply_row_cost($pdo, $warehouseRowId, $unitCost, null, true);

    return honey_pricing_serialize_warehouse_row($pdo, $warehouseRowId);
}

/** @return array<string, mixed> */
function honey_pricing_serialize_warehouse_row(PDO $pdo, int $warehouseRowId): array
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }

    return honey_pricing_enrich_warehouse_row($wh);
}

/** @param array<string, mixed> $row */
function honey_pricing_enrich_warehouse_row(array $row): array
{
    $unit = $row['unit_cost_per_gallon'] !== null ? round((float) $row['unit_cost_per_gallon'], 2) : null;
    $qty = (float) ($row['current_qty'] ?? 0);
    $priced = $unit !== null;
    $lineCost = $priced ? round($unit * $qty, 2) : null;

    return array_merge($row, [
        'unit_cost_per_gallon' => $unit,
        'supplier_price_id' => isset($row['supplier_price_id']) && $row['supplier_price_id'] !== null
            ? (int) $row['supplier_price_id'] : null,
        'cost_override' => (int) ($row['cost_override'] ?? 0) === 1,
        'pricing_status' => $priced ? 'priced' : 'pending',
        'inventory_value' => $lineCost,
    ]);
}

function honey_pricing_role_can_view_cost(array $user): bool
{
    return ims_user_can($user, 'honey.pricing.view_cost');
}

function honey_pricing_pending_count(PDO $pdo): int
{
    $st = $pdo->query('SELECT COUNT(*) FROM honey_warehouse_rows WHERE unit_cost_per_gallon IS NULL');

    return (int) ($st ? $st->fetchColumn() : 0);
}
