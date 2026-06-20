<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/suppliers.php';
require_once __DIR__ . '/honey_volume.php';
require_once __DIR__ . '/catalog_bootstrap.php';
require_once __DIR__ . '/material_inventory.php';
require_once __DIR__ . '/dept_production.php';

/** Lazy-load pricing helpers (avoids circular require at bootstrap). */
function honey_inventory_pricing(): void
{
    static $loaded = false;
    if (!$loaded) {
        require_once __DIR__ . '/honey_pricing.php';
        $loaded = true;
    }
}

const HONEY_VARIANTS = ['light', 'amber', 'dark', 'other'];
const HONEY_SIZES = ['liter', '250ml', '350ml', 'gallon'];

function honey_role_can_view(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'hr'], true);
}

function honey_role_can_mutate(string $role): bool
{
    return $role === 'prod_manager';
}

function honey_role_can_executive_edit(string $role): bool
{
    return $role === 'executive';
}

function honey_supplier_code(string $supplierName, string $arrivalDate): string
{
    $words = preg_split('/\s+/', trim($supplierName)) ?: [];
    $initials = '';
    foreach ($words as $w) {
        if ($w !== '') {
            $initials .= strtoupper($w[0]);
        }
    }
    if ($initials === '') {
        $initials = 'XX';
    }
    $ts = strtotime($arrivalDate);
    $mmddyy = $ts ? date('mdy', $ts) : date('mdy');

    return $initials . $mmddyy;
}

function honey_batch_no(string $supplierCode, string $bottlingDate): string
{
    $ts = strtotime($bottlingDate);
    $mmddyy = $ts ? date('mdy', $ts) : date('mdy');

    return $supplierCode . '-' . $mmddyy;
}

function honey_normalize_variant(string $v): string
{
    $v = strtolower(trim($v));
    if (!in_array($v, HONEY_VARIANTS, true)) {
        throw new RuntimeException('Invalid variant.');
    }

    return $v;
}

function honey_packaging_size_columns(string $size): array
{
    return match ($size) {
        'liter' => ['current_liter', 'initial_liter'],
        '250ml' => ['current_250ml', 'initial_250ml'],
        '350ml' => ['current_350ml', 'initial_350ml'],
        'gallon' => ['current_gallon', 'initial_gallon'],
        default => throw new RuntimeException('Invalid size.'),
    };
}

/** @return array<string, mixed>|null */
function honey_fetch_warehouse(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_warehouse_rows WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/** @return array<string, mixed>|null */
function honey_fetch_production(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_production_rows WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/** @return array<string, mixed>|null */
function honey_fetch_packaging(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_packaging_rows WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();

    return $row ?: null;
}

/** @return array<string, mixed>|null */
function honey_fetch_packaging_by_production(PDO $pdo, int $productionRowId): ?array
{
    $st = $pdo->prepare('SELECT * FROM honey_packaging_rows WHERE production_row_id = ? LIMIT 1');
    $st->execute([$productionRowId]);
    $row = $st->fetch();

    return $row ?: null;
}

function honey_warehouse_stock_in(PDO $pdo, int $userId, int $supplierId, string $arrivalDate, array $variants): array
{
    if ($supplierId < 1) {
        throw new RuntimeException('Select a supplier from the list.');
    }
    if (!$variants) {
        throw new RuntimeException('Add at least one variant row.');
    }
    $supplier = supplier_require_honey_active($pdo, $supplierId);
    $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
    $code = honey_supplier_code($supplierName, $arrivalDate);
    $created = [];
    $hasPricingCols = honey_warehouse_has_pricing_columns($pdo);
    if ($hasPricingCols) {
        $ins = $pdo->prepare(
            'INSERT INTO honey_warehouse_rows (
                supplier_id, supplier_name, supplier_code, arrival_date, variant,
                current_qty, initial_qty, unit_cost_per_gallon, supplier_price_id, cost_override, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, 0, ?)'
        );
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO honey_warehouse_rows (supplier_id, supplier_name, supplier_code, arrival_date, variant, current_qty, initial_qty, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
    }

    $variantsTouched = [];
    foreach ($variants as $row) {
        $variant = honey_normalize_variant((string) ($row['variant'] ?? ''));
        $qty = (float) ($row['qty'] ?? 0);
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }
        $ins->execute([$supplierId, $supplierName, $code, $arrivalDate, $variant, $qty, $qty, $userId]);
        $wid = (int) $pdo->lastInsertId();
        $pricing = ['priced' => false, 'unit_cost_per_gallon' => null, 'supplier_price_id' => null];
        if ($hasPricingCols) {
            honey_inventory_pricing();
            $pricing = honey_pricing_snapshot_warehouse_row($pdo, $wid);
            if ($pricing['priced']) {
                $variantsTouched[$variant] = true;
            }
        }
        $created[] = [
            'id' => $wid,
            'supplier_code' => $code,
            'variant' => $variant,
            'current_qty' => $qty,
            'initial_qty' => $qty,
            'unit_cost_per_gallon' => $pricing['unit_cost_per_gallon'],
            'pricing_status' => $pricing['priced'] ? 'priced' : 'pending',
        ];
    }

    if ($hasPricingCols) {
        honey_inventory_pricing();
        honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);
    }

    return ['supplier_code' => $code, 'rows' => $created];
}

function honey_warehouse_has_pricing_columns(PDO $pdo): bool
{
    return honey_warehouse_unit_cost_column($pdo) !== null;
}

/** @return 'unit_cost_per_gallon'|'unit_cost_per_liter'|null */
function honey_warehouse_unit_cost_column(PDO $pdo): ?string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache === '' ? null : $cache;
    }
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    foreach (['unit_cost_per_gallon', 'unit_cost_per_liter'] as $col) {
        $st->execute(['honey_warehouse_rows', $col]);
        if ($st->fetchColumn()) {
            $cache = $col;

            return $col;
        }
    }
    $cache = '';

    return null;
}

function honey_warehouse_normalize_cost_row(array $row): array
{
    if (!isset($row['unit_cost_per_gallon']) && isset($row['unit_cost_per_liter'])) {
        $row['unit_cost_per_gallon'] = $row['unit_cost_per_liter'];
        unset($row['unit_cost_per_liter']);
    }

    return $row;
}

function honey_warehouse_stock_out(PDO $pdo, int $userId, int $warehouseRowId, float $qty): array
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        throw new RuntimeException('Warehouse row not found.');
    }
    $current = (float) $wh['current_qty'];
    if ($qty <= 0 || $qty > $current) {
        throw new RuntimeException('Invalid quantity for stock-out.');
    }
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?');
        $upd->execute([$qty, $warehouseRowId, $qty]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Could not deduct warehouse quantity.');
        }
        $ins = $pdo->prepare(
            'INSERT INTO honey_production_rows (warehouse_row_id, supplier_code, arrival_date, variant, current_qty, initial_qty, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $warehouseRowId,
            $wh['supplier_code'],
            $wh['arrival_date'],
            $wh['variant'],
            $qty,
            $qty,
            $userId,
        ]);
        $prodId = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['production_row_id' => $prodId, 'qty' => $qty];
}

/**
 * Move bottled product from production to packaging.
 * Gallons deducted = auto from bottle line volumes. One packaging row per production row.
 *
 * @param list<array{size:string, qty:float}> $lines
 */
function honey_production_stock_out(PDO $pdo, int $userId, int $productionRowId, array $lines): array
{
    $pr = honey_fetch_production($pdo, $productionRowId);
    if (!$pr) {
        throw new RuntimeException('Production row not found.');
    }
    if (!$lines) {
        throw new RuntimeException('Add at least one bottle line with quantity.');
    }

    $sizeQtys = honey_aggregate_bottle_lines($lines);
    $usedGallons = honey_lines_to_gallons($lines);
    if ($usedGallons <= 0) {
        throw new RuntimeException('Enter quantity for at least one bottle.');
    }

    $available = (float) $pr['current_qty'];
    if ($usedGallons > $available + 1e-9) {
        $usedFriendly = round($usedGallons, 1);
        $availFriendly = round($available, 1);
        throw new RuntimeException(
            "Bottled volume uses {$usedFriendly} gallon/s but only {$availFriendly} gallon/s remain in production."
        );
    }

    $liter = $sizeQtys['liter'];
    $ml250 = $sizeQtys['250ml'];
    $ml350 = $sizeQtys['350ml'];
    $gallon = $sizeQtys['gallon'];

    $existing = honey_fetch_packaging_by_production($pdo, $productionRowId);
    $isNew = $existing === null;
    if ($isNew) {
        $bottlingDate = date('Y-m-d');
        $batchNo = honey_batch_no((string) $pr['supplier_code'], $bottlingDate);
    } else {
        $bottlingDate = (string) $existing['bottling_date'];
        $batchNo = (string) $existing['batch_no'];
    }

    $packagingDeduct = [];
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE honey_production_rows SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?'
        );
        $upd->execute([$usedGallons, $productionRowId, $usedGallons]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Could not deduct production quantity.');
        }

        if ($isNew) {
            $ins = $pdo->prepare(
                'INSERT INTO honey_packaging_rows (
                    production_row_id, supplier_code, batch_no, bottling_date, variant,
                    current_liter, initial_liter, current_250ml, initial_250ml,
                    current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $productionRowId,
                $pr['supplier_code'],
                $batchNo,
                $bottlingDate,
                $pr['variant'],
                $liter, $liter,
                $ml250, $ml250,
                $ml350, $ml350,
                $gallon, $gallon,
                $userId,
            ]);
            $packId = (int) $pdo->lastInsertId();
        } else {
            $packId = (int) $existing['id'];
            $pdo->prepare(
                'UPDATE honey_packaging_rows SET
                    current_liter = current_liter + ?, initial_liter = initial_liter + ?,
                    current_250ml = current_250ml + ?, initial_250ml = initial_250ml + ?,
                    current_350ml = current_350ml + ?, initial_350ml = initial_350ml + ?,
                    current_gallon = current_gallon + ?, initial_gallon = initial_gallon + ?
                 WHERE id = ?'
            )->execute([$liter, $liter, $ml250, $ml250, $ml350, $ml350, $gallon, $gallon, $packId]);
        }

        $packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $remaining = $available - $usedGallons;

    return [
        'packaging_row_id' => $packId,
        'batch_no' => $batchNo,
        'bottling_date' => $bottlingDate,
        'used_gallons' => round($usedGallons, 4),
        'remaining_production_gallons' => round(max(0, $remaining), 4),
        'is_new_packaging_row' => $isNew,
        'packaging_deduct' => $packagingDeduct ?? [],
    ];
}

/**
 * Deduct per-unit honey packaging from stocks.on_hand when bottling.
 *
 * @param array<string, float> $sizeQtys
 * @return list<array<string, mixed>>
 */
function honey_bottling_deduct_packaging_stocks(PDO $pdo, int $userId, array $sizeQtys, string $label): array
{
    if (!material_inventory_has_pool_columns($pdo)) {
        return [];
    }

    $deptSt = $pdo->query("SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1");
    $deptId = $deptSt ? (int) $deptSt->fetchColumn() : 0;
    if ($deptId < 1) {
        return [];
    }

    $applied = [];
    $totalBottles = 0.0;
    foreach (HONEY_SIZES as $size) {
        $qty = (float) ($sizeQtys[$size] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $totalBottles += $qty;
        $bottleName = catalog_honey_bottle_stock_name($size);
        $st = $pdo->prepare(
            'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id IS NULL AND production_deduct = 1 LIMIT 1'
        );
        $st->execute([$deptId, $bottleName]);
        $stockId = (int) $st->fetchColumn();
        if ($stockId < 1) {
            continue;
        }
        $res = dept_packaging_deduct($pdo, $stockId, $qty, $userId, 'bottling: ' . substr($label, 0, 100));
        $applied[] = [
            'stock_name' => $bottleName,
            'qty' => $qty,
            'on_hand' => $res['on_hand'],
        ];
    }

    if ($totalBottles > 0) {
        $st = $pdo->prepare(
            'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id IS NULL AND production_deduct = 1 LIMIT 1'
        );
        $st->execute([$deptId, CATALOG_HONEY_STICKER_STOCK_NAME]);
        $stickerId = (int) $st->fetchColumn();
        if ($stickerId > 0) {
            $res = dept_packaging_deduct($pdo, $stickerId, $totalBottles, $userId, 'bottling: ' . substr($label, 0, 100));
            $applied[] = [
                'stock_name' => CATALOG_HONEY_STICKER_STOCK_NAME,
                'qty' => $totalBottles,
                'on_hand' => $res['on_hand'],
            ];
        }
    }

    return $applied;
}

function honey_packaging_stock_out_sold(PDO $pdo, int $userId, int $packagingRowId, array $sizeQtys, string $saleType): array
{
    $pk = honey_fetch_packaging($pdo, $packagingRowId);
    if (!$pk) {
        throw new RuntimeException('Packaging row not found.');
    }
    if (!in_array($saleType, ['walk_in', 'parcel'], true)) {
        throw new RuntimeException('Invalid sale type.');
    }

    $deducts = [];
    foreach (HONEY_SIZES as $size) {
        $q = (float) ($sizeQtys[$size] ?? 0);
        if ($q <= 0) {
            continue;
        }
        [$curCol] = honey_packaging_size_columns($size);
        $avail = (float) $pk[$curCol];
        if ($q > $avail) {
            throw new RuntimeException("Not enough {$size} available.");
        }
        $deducts[$size] = $q;
    }
    if (!$deducts) {
        throw new RuntimeException('Select at least one size quantity to sell.');
    }

    $pdo->beginTransaction();
    try {
        $sets = [];
        $params = [];
        foreach ($deducts as $size => $q) {
            [$curCol] = honey_packaging_size_columns($size);
            $sets[] = "{$curCol} = {$curCol} - ?";
            $params[] = $q;
        }
        $params[] = $packagingRowId;
        $sql = 'UPDATE honey_packaging_rows SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);

        $ins = $pdo->prepare(
            'INSERT INTO honey_sales_log (source, packaging_row_id, batch_no, variant, qty_liter, qty_250ml, qty_350ml, qty_gallon, sale_type, sold_by)
             VALUES ("packaging", ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $packagingRowId,
            $pk['batch_no'],
            $pk['variant'],
            $deducts['liter'] ?? 0,
            $deducts['250ml'] ?? 0,
            $deducts['350ml'] ?? 0,
            $deducts['gallon'] ?? 0,
            $saleType,
            $userId,
        ]);
        $saleId = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['sale_id' => $saleId, 'deducted' => $deducts];
}

function honey_apply_correction(PDO $pdo, string $stage, string $rowTable, int $rowId, string $action, float $amount, ?string $size): void
{
    honey_apply_correction_request($pdo, [
        'correction_type' => 'qty_delta',
        'stage' => $stage,
        'row_table' => $rowTable,
        'row_id' => $rowId,
        'action' => $action,
        'amount' => $amount,
        'size' => $size,
        'new_value' => null,
    ]);
}

/** @param array<string, mixed> $req */
function honey_apply_correction_request(PDO $pdo, array $req): void
{
    $type = (string) ($req['correction_type'] ?? 'qty_delta');
    $stage = (string) ($req['stage'] ?? '');
    $rowTable = (string) ($req['row_table'] ?? '');
    $rowId = (int) ($req['row_id'] ?? 0);
    $newValue = isset($req['new_value']) ? trim((string) $req['new_value']) : '';

    if ($type === 'qty_delta') {
        $action = (string) ($req['action'] ?? '');
        $amount = (float) ($req['amount'] ?? 0);
        $size = $req['size'] !== null ? (string) $req['size'] : null;
        $delta = $action === 'add' ? $amount : -$amount;
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be positive.');
        }
        if ($rowTable === 'honey_warehouse_rows' && $stage === 'warehouse') {
            $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = current_qty + ? WHERE id = ?')->execute([$delta, $rowId]);

            return;
        }
        if ($rowTable === 'honey_production_rows' && $stage === 'production') {
            $pdo->prepare('UPDATE honey_production_rows SET current_qty = current_qty + ? WHERE id = ?')->execute([$delta, $rowId]);

            return;
        }
        if ($rowTable === 'honey_packaging_rows' && $stage === 'packaging') {
            if ($size === null || $size === '') {
                throw new RuntimeException('Size required for packaging correction.');
            }
            [$curCol] = honey_packaging_size_columns($size);
            $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = {$curCol} + ? WHERE id = ?")->execute([$delta, $rowId]);

            return;
        }
        if ($rowTable === 'dept_inventory_rows' && $stage === 'dept') {
            $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = current_qty + ? WHERE id = ?')->execute([$delta, $rowId]);

            return;
        }
        throw new RuntimeException('Unsupported correction target.');
    }

    if ($type === 'set_qty') {
        $qty = (float) $newValue;
        if ($qty < 0) {
            throw new RuntimeException('Quantity cannot be negative.');
        }
        if ($rowTable === 'honey_warehouse_rows') {
            $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);

            return;
        }
        if ($rowTable === 'honey_production_rows') {
            $pdo->prepare('UPDATE honey_production_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);

            return;
        }
        if ($rowTable === 'dept_inventory_rows') {
            $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);

            return;
        }
        throw new RuntimeException('Set quantity not supported for this row.');
    }

    if ($type === 'variant') {
        $variant = honey_normalize_variant($newValue);
        if ($rowTable === 'honey_warehouse_rows') {
            $pdo->prepare('UPDATE honey_warehouse_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);

            return;
        }
        if ($rowTable === 'honey_production_rows') {
            $pdo->prepare('UPDATE honey_production_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            $pdo->prepare(
                'UPDATE honey_packaging_rows pk
                 INNER JOIN honey_production_rows pr ON pr.id = pk.production_row_id
                 SET pk.variant = ? WHERE pr.id = ?'
            )->execute([$variant, $rowId]);

            return;
        }
        if ($rowTable === 'honey_packaging_rows') {
            $pdo->prepare('UPDATE honey_packaging_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);

            return;
        }
        throw new RuntimeException('Variant change not supported for this row.');
    }

    if ($type === 'supplier') {
        if ($rowTable !== 'honey_warehouse_rows') {
            throw new RuntimeException('Supplier can only be changed on warehouse rows.');
        }
        $supplierId = (int) $newValue;
        if ($supplierId < 1) {
            throw new RuntimeException('Invalid supplier.');
        }
        $supplier = supplier_require_honey_active($pdo, $supplierId);
        $wh = honey_fetch_warehouse($pdo, $rowId);
        if (!$wh) {
            throw new RuntimeException('Warehouse row not found.');
        }
        $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
        $code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);
        $pdo->prepare(
            'UPDATE honey_warehouse_rows SET supplier_id = ?, supplier_name = ?, supplier_code = ? WHERE id = ?'
        )->execute([$supplierId, $supplierName, $code, $rowId]);
        honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);

        return;
    }

    if ($type === 'packaging_size') {
        if ($rowTable !== 'honey_packaging_rows') {
            throw new RuntimeException('Packaging size correction requires packaging row.');
        }
        $parts = explode(':', $newValue, 2);
        $size = $parts[0] ?? '';
        $qty = (float) ($parts[1] ?? 0);
        if ($qty < 0) {
            throw new RuntimeException('Quantity cannot be negative.');
        }
        [$curCol, $initCol] = honey_packaging_size_columns($size);
        $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$initCol} = ? WHERE id = ?")->execute([$qty, $qty, $rowId]);

        return;
    }

    throw new RuntimeException('Unknown correction type.');
}

function honey_cascade_warehouse_metadata(PDO $pdo, int $warehouseRowId, ?int $supplierId, ?string $variant): void
{
    $wh = honey_fetch_warehouse($pdo, $warehouseRowId);
    if (!$wh) {
        return;
    }
    $code = (string) $wh['supplier_code'];
    $variantVal = (string) $wh['variant'];
    if ($supplierId !== null) {
        $supplier = supplier_fetch($pdo, $supplierId);
        if ($supplier) {
            $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
            $code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);
        }
    }
    if ($variant !== null) {
        $variantVal = $variant;
    }
    $pdo->prepare(
        'UPDATE honey_production_rows SET supplier_code = ?, variant = ? WHERE warehouse_row_id = ?'
    )->execute([$code, $variantVal, $warehouseRowId]);
    $pdo->prepare(
        'UPDATE honey_packaging_rows pk
         INNER JOIN honey_production_rows pr ON pr.id = pk.production_row_id
         SET pk.supplier_code = ?, pk.variant = ?
         WHERE pr.warehouse_row_id = ?'
    )->execute([$code, $variantVal, $warehouseRowId]);
}

function honey_log_edit(PDO $pdo, int $userId, string $rowTable, int $rowId, string $field, ?string $old, ?string $new): void
{
    try {
        $pdo->prepare(
            'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$rowTable, $rowId, $field, $old, $new, $userId]);
    } catch (Throwable) {
        // Log table may not exist before migration.
    }
}

function honey_traceability(PDO $pdo, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return ['matches' => []];
    }

    $matches = [];
    if (preg_match('/^[A-Z]{2,10}\d{6}-\d{6}$/i', $query)) {
        $st = $pdo->prepare('SELECT * FROM honey_packaging_rows WHERE batch_no = ? LIMIT 20');
        $st->execute([strtoupper($query)]);
        foreach ($st->fetchAll() as $pk) {
            $matches[] = honey_build_trace_chain($pdo, $pk);
        }

        return ['matches' => $matches];
    }
    if (preg_match('/^[A-Z]{2,10}\d{6}$/i', $query)) {
        $code = strtoupper($query);
        $st = $pdo->prepare('SELECT * FROM honey_packaging_rows WHERE supplier_code = ? OR batch_no LIKE ? LIMIT 50');
        $st->execute([$code, $code . '-%']);
        foreach ($st->fetchAll() as $pk) {
            $matches[] = honey_build_trace_chain($pdo, $pk);
        }

        return ['matches' => $matches];
    }

    $st = $pdo->prepare(
        'SELECT pk.* FROM honey_packaging_rows pk
         INNER JOIN honey_production_rows pr ON pr.id = pk.production_row_id
         INNER JOIN honey_warehouse_rows wh ON wh.id = pr.warehouse_row_id
         WHERE wh.supplier_name LIKE ? LIMIT 30'
    );
    $st->execute(['%' . $query . '%']);
    foreach ($st->fetchAll() as $pk) {
        $matches[] = honey_build_trace_chain($pdo, $pk);
    }

    return ['matches' => $matches];
}

/** @param array<string, mixed> $pk */
function honey_build_trace_chain(PDO $pdo, array $pk): array
{
    $pr = honey_fetch_production($pdo, (int) $pk['production_row_id']);
    $wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;
    $st = $pdo->prepare('SELECT * FROM honey_sales_log WHERE packaging_row_id = ? ORDER BY created_at DESC');
    $st->execute([(int) $pk['id']]);
    $sales = $st->fetchAll();

    return [
        'batch_no' => $pk['batch_no'],
        'packaging' => $pk,
        'production' => $pr,
        'warehouse' => $wh,
        'sales' => $sales,
    ];
}

function honey_correction_row_options(PDO $pdo, string $stage): array
{
    return match ($stage) {
        'warehouse' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'label' => $r['supplier_code'] . ' · ' . $r['variant'] . ' · ' . $r['current_qty'] . '/' . $r['initial_qty'],
            'row_table' => 'honey_warehouse_rows',
        ], $pdo->query('SELECT id, supplier_code, variant, current_qty, initial_qty FROM honey_warehouse_rows ORDER BY id DESC LIMIT 500')->fetchAll()),
        'production' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'label' => $r['supplier_code'] . ' · ' . $r['variant'] . ' · ' . $r['current_qty'] . '/' . $r['initial_qty'],
            'row_table' => 'honey_production_rows',
        ], $pdo->query('SELECT id, supplier_code, variant, current_qty, initial_qty FROM honey_production_rows ORDER BY id DESC LIMIT 500')->fetchAll()),
        'packaging' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'label' => $r['batch_no'] . ' · ' . $r['variant'],
            'row_table' => 'honey_packaging_rows',
        ], $pdo->query('SELECT id, batch_no, variant FROM honey_packaging_rows ORDER BY id DESC LIMIT 500')->fetchAll()),
        default => [],
    };
}

function honey_executive_adjust(PDO $pdo, string $stage, string $rowTable, int $rowId, float $newCurrent, ?string $size = null): void
{
    honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [
        'current_qty' => $newCurrent,
        'size' => $size,
    ]);
}

/** @param array<string, mixed> $fields */
function honey_executive_qty_resets_baseline(array $fields): bool
{
    return ($fields['qty_mode'] ?? 'current_only') === 'set_exact';
}

/** @param array<string, mixed> $fields */
function honey_executive_edit_row(PDO $pdo, int $userId, string $stage, string $rowTable, int $rowId, array $fields): void
{
    if ($rowTable === 'honey_warehouse_rows') {
        $wh = honey_fetch_warehouse($pdo, $rowId);
        if (!$wh) {
            throw new RuntimeException('Warehouse row not found.');
        }
        if (isset($fields['supplier_id'])) {
            $supplierId = (int) $fields['supplier_id'];
            $supplier = supplier_require_honey_active($pdo, $supplierId);
            $supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);
            $code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId);
            $pdo->prepare(
                'UPDATE honey_warehouse_rows SET supplier_id = ?, supplier_name = ?, supplier_code = ? WHERE id = ?'
            )->execute([$supplierId, $supplierName, $code, $rowId]);
            honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);
        }
        if (isset($fields['variant'])) {
            $variant = honey_normalize_variant((string) $fields['variant']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant);
            $pdo->prepare('UPDATE honey_warehouse_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);
        }
        if (isset($fields['current_qty'])) {
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            $resetBaseline = honey_executive_qty_resets_baseline($fields);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty);
                $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare('UPDATE honey_warehouse_rows SET current_qty = ? WHERE id = ?')->execute([$qty, $rowId]);
            }
        }

        return;
    }

    if ($rowTable === 'honey_production_rows') {
        $pr = honey_fetch_production($pdo, $rowId);
        if (!$pr) {
            throw new RuntimeException('Production row not found.');
        }
        if (isset($fields['variant'])) {
            $variant = honey_normalize_variant((string) $fields['variant']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant);
            $pdo->prepare('UPDATE honey_production_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
            $pdo->prepare(
                'UPDATE honey_packaging_rows SET variant = ? WHERE production_row_id = ?'
            )->execute([$variant, $rowId]);
        }
        if (isset($fields['current_qty'])) {
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            $resetBaseline = honey_executive_qty_resets_baseline($fields);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty);
                $pdo->prepare('UPDATE honey_production_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare('UPDATE honey_production_rows SET current_qty = ? WHERE id = ?')->execute([$qty, $rowId]);
            }
        }

        return;
    }

    if ($rowTable === 'honey_packaging_rows') {
        $pk = honey_fetch_packaging($pdo, $rowId);
        if (!$pk) {
            throw new RuntimeException('Packaging row not found.');
        }
        if (isset($fields['variant'])) {
            $variant = honey_normalize_variant((string) $fields['variant']);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant);
            $pdo->prepare('UPDATE honey_packaging_rows SET variant = ? WHERE id = ?')->execute([$variant, $rowId]);
        }
        $sizeQtys = $fields['size_qtys'] ?? null;
        $resetBaseline = honey_executive_qty_resets_baseline($fields);
        if (is_array($sizeQtys)) {
            foreach ($sizeQtys as $size => $qty) {
                $qty = (float) $qty;
                if ($qty < 0) {
                    throw new RuntimeException('Quantity cannot be negative.');
                }
                [$curCol, $initCol] = honey_packaging_size_columns((string) $size);
                $old = (string) ($pk[$curCol] ?? '0');
                honey_log_edit($pdo, $userId, $rowTable, $rowId, $curCol, $old, (string) $qty);
                if ($resetBaseline) {
                    honey_log_edit($pdo, $userId, $rowTable, $rowId, $initCol, (string) ($pk[$initCol] ?? '0'), (string) $qty);
                    $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$initCol} = ? WHERE id = ?")->execute([$qty, $qty, $rowId]);
                } else {
                    $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ? WHERE id = ?")->execute([$qty, $rowId]);
                }
            }
        } elseif (isset($fields['current_qty'], $fields['size'])) {
            $size = (string) $fields['size'];
            $qty = (float) $fields['current_qty'];
            [$curCol, $initCol] = honey_packaging_size_columns($size);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, $curCol, (string) ($pk[$curCol] ?? '0'), (string) $qty);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, $initCol, (string) ($pk[$initCol] ?? '0'), (string) $qty);
                $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$initCol} = ? WHERE id = ?")->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ? WHERE id = ?")->execute([$qty, $rowId]);
            }
        }

        return;
    }

    if ($rowTable === 'dept_inventory_rows') {
        $st = $pdo->prepare('SELECT * FROM dept_inventory_rows WHERE id = ? LIMIT 1');
        $st->execute([$rowId]);
        $row = $st->fetch();
        if (!$row) {
            throw new RuntimeException('Dept row not found.');
        }
        if (isset($fields['product_label'])) {
            $label = trim((string) $fields['product_label']);
            if ($label !== '') {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label);
                $pdo->prepare('UPDATE dept_inventory_rows SET product_label = ? WHERE id = ?')->execute([$label, $rowId]);
            }
        }
        if (isset($fields['current_qty'])) {
            $qty = (float) $fields['current_qty'];
            if ($qty < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }
            $resetBaseline = honey_executive_qty_resets_baseline($fields);
            honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty);
            if ($resetBaseline) {
                honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty);
                $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')->execute([$qty, $qty, $rowId]);
            } else {
                $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ? WHERE id = ?')->execute([$qty, $rowId]);
            }
        }

        return;
    }

    throw new RuntimeException('Executive edit not supported for this row.');
}

/** @return array<string, mixed>|null */
function honey_correction_row_context(PDO $pdo, array $req): ?array
{
    $rowTable = (string) ($req['row_table'] ?? '');
    $rowId = (int) ($req['row_id'] ?? 0);
    if ($rowId < 1) {
        return null;
    }

    if ($rowTable === 'honey_warehouse_rows') {
        $wh = honey_fetch_warehouse($pdo, $rowId);
        if (!$wh) {
            return null;
        }

        return [
            'stage_label' => 'Warehouse',
            'supplier_code' => (string) $wh['supplier_code'],
            'supplier_name' => (string) $wh['supplier_name'],
            'arrival_date' => (string) $wh['arrival_date'],
            'variant' => (string) $wh['variant'],
            'current_qty' => (float) $wh['current_qty'],
            'initial_qty' => (float) $wh['initial_qty'],
            'qty_unit' => 'gallon/s',
        ];
    }

    if ($rowTable === 'honey_production_rows') {
        $pr = honey_fetch_production($pdo, $rowId);
        if (!$pr) {
            return null;
        }

        return [
            'stage_label' => 'Production',
            'supplier_code' => (string) $pr['supplier_code'],
            'arrival_date' => (string) $pr['arrival_date'],
            'variant' => (string) $pr['variant'],
            'current_qty' => (float) $pr['current_qty'],
            'initial_qty' => (float) $pr['initial_qty'],
            'qty_unit' => 'gallon/s',
        ];
    }

    if ($rowTable === 'honey_packaging_rows') {
        $pk = honey_fetch_packaging($pdo, $rowId);
        if (!$pk) {
            return null;
        }

        return [
            'stage_label' => 'Packaging',
            'batch_no' => (string) $pk['batch_no'],
            'supplier_code' => (string) $pk['supplier_code'],
            'bottling_date' => (string) $pk['bottling_date'],
            'variant' => (string) $pk['variant'],
            'qty_unit' => 'bottles',
            'sizes' => [
                'liter' => (float) $pk['current_liter'],
                '250ml' => (float) $pk['current_250ml'],
                '350ml' => (float) $pk['current_350ml'],
                'gallon' => (float) $pk['current_gallon'],
            ],
            'initial_sizes' => [
                'liter' => (float) $pk['initial_liter'],
                '250ml' => (float) $pk['initial_250ml'],
                '350ml' => (float) $pk['initial_350ml'],
                'gallon' => (float) $pk['initial_gallon'],
            ],
        ];
    }

    if ($rowTable === 'dept_inventory_rows') {
        $st = $pdo->prepare('SELECT dir.*, d.name AS dept_name FROM dept_inventory_rows dir INNER JOIN departments d ON d.id = dir.dept_id WHERE dir.id = ?');
        $st->execute([$rowId]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        return [
            'stage_label' => 'Dept — ' . (string) $row['dept_name'],
            'product_label' => (string) $row['product_label'],
            'current_qty' => (float) $row['current_qty'],
            'initial_qty' => (float) $row['initial_qty'],
            'qty_unit' => 'units',
        ];
    }

    return null;
}

/** @param array<string, mixed>|null $ctx */
function honey_correction_change_preview(PDO $pdo, array $req, ?array $ctx): array
{
    $type = (string) ($req['correction_type'] ?? 'qty_delta');
    $amount = (float) ($req['amount'] ?? 0);
    $action = (string) ($req['action'] ?? '');
    $newValue = (string) ($req['new_value'] ?? '');
    $size = $req['size'] !== null && $req['size'] !== '' ? (string) $req['size'] : null;

    if (!$ctx) {
        return [
            'headline' => honey_correction_summary($req),
            'detail' => null,
            'before_display' => null,
            'after_display' => null,
        ];
    }

    if ($type === 'variant') {
        $after = honey_normalize_variant($newValue);

        return [
            'headline' => 'Change variant',
            'detail' => ucfirst((string) $ctx['variant']) . ' → ' . ucfirst($after),
            'before_display' => ucfirst((string) $ctx['variant']),
            'after_display' => ucfirst($after),
        ];
    }

    if ($type === 'supplier') {
        $supplierId = (int) $newValue;
        $supplier = supplier_fetch($pdo, $supplierId);
        $newName = $supplier
            ? supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name'])
            : "Supplier #{$supplierId}";
        $newCode = $supplier && isset($ctx['arrival_date'])
            ? honey_supplier_code($newName, (string) $ctx['arrival_date'])
            : '—';

        return [
            'headline' => 'Change supplier',
            'detail' => ($ctx['supplier_name'] ?? $ctx['supplier_code'] ?? '') . ' → ' . $newName,
            'before_display' => (string) ($ctx['supplier_code'] ?? '') . ' · ' . ($ctx['supplier_name'] ?? ''),
            'after_display' => $newCode . ' · ' . $newName,
        ];
    }

    if ($type === 'packaging_size') {
        $parts = explode(':', $newValue, 2);
        $sz = $parts[0] ?? '';
        $qty = (float) ($parts[1] ?? 0);
        $sizes = $ctx['sizes'] ?? [];
        $initial = $ctx['initial_sizes'] ?? [];
        $before = (float) ($sizes[$sz] ?? 0);
        $init = (float) ($initial[$sz] ?? 0);

        return [
            'headline' => "Set {$sz} bottle count",
            'detail' => "{$before} → {$qty} bottles",
            'before_display' => "{$before} / {$init}",
            'after_display' => "{$qty} / {$qty}",
            'size' => $sz,
        ];
    }

    if ($type === 'set_qty') {
        $qty = (float) $newValue;
        $cur = (float) ($ctx['current_qty'] ?? 0);
        $init = (float) ($ctx['initial_qty'] ?? 0);
        $unit = (string) ($ctx['qty_unit'] ?? '');

        return [
            'headline' => 'Set quantity',
            'detail' => "{$cur} → {$qty} {$unit} (resets baseline)",
            'before_display' => "{$cur} / {$init} {$unit}",
            'after_display' => "{$qty} / {$qty} {$unit}",
        ];
    }

    // qty_delta
    if (isset($ctx['sizes']) && $size) {
        $sizes = $ctx['sizes'];
        $initial = $ctx['initial_sizes'] ?? [];
        $cur = (float) ($sizes[$size] ?? 0);
        $init = (float) ($initial[$size] ?? 0);
        $delta = $action === 'add' ? $amount : -$amount;
        $after = max(0, $cur + $delta);

        return [
            'headline' => ucfirst($action) . " {$amount} {$size} bottles",
            'detail' => "Packaging size: {$size}",
            'before_display' => "{$cur} / {$init}",
            'after_display' => "{$after} / {$init}",
            'size' => $size,
        ];
    }

    $cur = (float) ($ctx['current_qty'] ?? 0);
    $init = (float) ($ctx['initial_qty'] ?? 0);
    $unit = (string) ($ctx['qty_unit'] ?? '');
    $delta = $action === 'add' ? $amount : -$amount;
    $after = max(0, $cur + $delta);

    return [
        'headline' => ucfirst($action) . " {$amount} {$unit}",
        'detail' => null,
        'before_display' => "{$cur} / {$init} {$unit}",
        'after_display' => "{$after} / {$init} {$unit}",
    ];
}

/** @param array<string, mixed> $req */
function honey_correction_enrich(PDO $pdo, array &$req): void
{
    $ctx = honey_correction_row_context($pdo, $req);
    $req['row_context'] = $ctx;
    $req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);
    $req['summary'] = honey_correction_summary($req);
}

/** @param array<string, mixed> $req */
function honey_correction_summary(array $req): string
{
    $type = (string) ($req['correction_type'] ?? 'qty_delta');
    $stage = (string) ($req['stage'] ?? '');
    $nv = (string) ($req['new_value'] ?? '');

    return match ($type) {
        'set_qty' => "Set qty to {$nv} ({$stage})",
        'variant' => "Change variant to {$nv}",
        'supplier' => 'Change supplier',
        'packaging_size' => 'Set packaging ' . str_replace(':', ' = ', $nv),
        default => ucfirst((string) ($req['action'] ?? '')) . ' ' . ($req['amount'] ?? '')
            . ($req['size'] ? ' (' . $req['size'] . ')' : '') . " ({$stage})",
    };
}

function honey_correction_unread_count(PDO $pdo): int
{
    try {
        $st = $pdo->query(
            'SELECT COUNT(*) FROM inventory_correction_requests
             WHERE status = "pending" AND executive_viewed_at IS NULL'
        );

        return (int) $st->fetchColumn();
    } catch (Throwable) {
        $st = $pdo->query('SELECT COUNT(*) FROM inventory_correction_requests WHERE status = "pending"');

        return (int) $st->fetchColumn();
    }
}

/** @return list<array<string, mixed>> */
function honey_correction_unread_list(PDO $pdo, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    try {
        $st = $pdo->prepare(
            "SELECT icr.*, u.name AS requested_by_name
             FROM inventory_correction_requests icr
             INNER JOIN users u ON u.id = icr.requested_by
             WHERE icr.status = 'pending' AND icr.executive_viewed_at IS NULL
             ORDER BY icr.created_at DESC
             LIMIT {$limit}"
        );
        $st->execute();
    } catch (Throwable) {
        $st = $pdo->prepare(
            "SELECT icr.*, u.name AS requested_by_name
             FROM inventory_correction_requests icr
             INNER JOIN users u ON u.id = icr.requested_by
             WHERE icr.status = 'pending'
             ORDER BY icr.created_at DESC
             LIMIT {$limit}"
        );
        $st->execute();
    }
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        honey_correction_enrich($pdo, $r);
    }

    return $rows;
}

function honey_correction_mark_viewed(PDO $pdo, int $requestId): void
{
    try {
        $pdo->prepare(
            'UPDATE inventory_correction_requests SET executive_viewed_at = NOW()
             WHERE id = ? AND status = "pending" AND executive_viewed_at IS NULL'
        )->execute([$requestId]);
    } catch (Throwable) {
        // Column may not exist pre-migration.
    }
}

/** Display labels for packaging size breakdown (matches Sales UI). */
const HONEY_PACKAGING_SIZE_LABELS = [
    '250ml' => '250ML',
    '350ml' => '350ML',
    'liter' => '1L',
    'gallon' => 'GALLON',
];

/**
 * Aggregate current on-hand bottles from packaging rows with batch numbers only.
 * Excludes warehouse and production (not bottled / no batch yet).
 *
 * @param list<array<string, mixed>> $rows SQL rows with variant, ml250, ml350, liter, gallon
 * @return array{variants: list<array<string, mixed>>, total: float, batch_count: int}
 */
function honey_packaging_variant_inventory_breakdown(array $rows): array
{
    $sizeKeys = [
        ['key' => '250ml', 'col' => 'ml250'],
        ['key' => '350ml', 'col' => 'ml350'],
        ['key' => 'liter', 'col' => 'liter'],
        ['key' => 'gallon', 'col' => 'gallon'],
    ];

    $variants = [];
    $grandTotal = 0.0;
    $batchCount = 0;

    foreach ($rows as $row) {
        $batchCount += (int) ($row['batch_rows'] ?? 0);
        $variantTotal = 0.0;
        foreach ($sizeKeys as $sk) {
            $variantTotal += (float) ($row[$sk['col']] ?? 0);
        }
        $grandTotal += $variantTotal;
        $sizes = [];
        foreach ($sizeKeys as $sk) {
            $qty = (float) ($row[$sk['col']] ?? 0);
            $sizes[] = [
                'size' => $sk['key'],
                'label' => HONEY_PACKAGING_SIZE_LABELS[$sk['key']] ?? strtoupper($sk['key']),
                'qty' => $qty,
                'pct' => $variantTotal > 0 ? round(($qty / $variantTotal) * 100, 1) : 0.0,
            ];
        }
        $variants[] = [
            'variant' => (string) ($row['variant'] ?? ''),
            'total_bottles' => $variantTotal,
            'sizes' => $sizes,
        ];
    }

    return ['variants' => $variants, 'total' => $grandTotal, 'batch_count' => $batchCount];
}

/** @return array<string, mixed> */
function honey_packaging_total_inventory_snapshot(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT variant,
                COALESCE(SUM(current_250ml), 0) AS ml250,
                COALESCE(SUM(current_350ml), 0) AS ml350,
                COALESCE(SUM(current_liter), 0) AS liter,
                COALESCE(SUM(current_gallon), 0) AS gallon,
                COUNT(*) AS batch_rows
         FROM honey_packaging_rows
         WHERE batch_no IS NOT NULL AND TRIM(batch_no) <> ""
           AND variant IN ("light", "amber", "dark")
         GROUP BY variant
         ORDER BY FIELD(variant, "light", "amber", "dark")'
    );
    $agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);

    $byVariant = [];
    foreach ($agg['variants'] as $v) {
        $byVariant[$v['variant']] = $v;
    }

    $ordered = [];
    foreach (['light', 'amber', 'dark'] as $key) {
        if (isset($byVariant[$key])) {
            $ordered[] = $byVariant[$key];
        }
    }

    return [
        'scope' => 'packaging_batches_only',
        'note' => 'Counts bottled stock with batch numbers. Warehouse and production gallons are excluded.',
        'total_bottles' => $agg['total'],
        'batch_count' => $agg['batch_count'],
        'variants' => $ordered,
    ];
}
