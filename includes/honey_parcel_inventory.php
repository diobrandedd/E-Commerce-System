<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/parcel_pack_costing.php';
require_once __DIR__ . '/rts_schema.php';

/**
 * Apply packaging + dept deduct for a shipped online parcel.
 *
 * @return array{
 *   deducted: list<array{variant: string, size: string, qty: float}>,
 *   dept_deducted: list<array{product_key: string, qty: float}>,
 *   lines: list<array{packaging_row_id: int, variant: string, size: string, qty: float}>,
 *   fulfillment_deducted: list<array{stock_id: int, qty: float, unit: string}>
 * }
 */
function honey_parcel_apply_deduct(PDO $pdo, int $parcelId, int $userId, string $itemsRaw): array
{
    $parsed = honey_parcel_parse_items_string($itemsRaw);
    if ($parsed['errors'] !== []) {
        throw new RuntimeException(implode(' ', $parsed['errors']));
    }

    $deptItems = [];
    foreach ($parsed['other_items'] as $other) {
        $key = isset($other['product_key']) ? trim((string) $other['product_key']) : '';
        if ($key === '') {
            $key = dept_inventory_resolve_other_item((string) ($other['label'] ?? '')) ?? '';
        }
        if ($key === '' || !dept_product_key_valid($key)) {
            continue;
        }
        $qty = (float) ($other['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $deptItems[$key] = ($deptItems[$key] ?? 0) + $qty;
    }

    if ($parsed['deltas'] === [] && $deptItems === []) {
        throw new RuntimeException('No inventory lines to deduct for this parcel.');
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $allLines = [];
        $deptDeducted = [];
        $fulfillmentDeducted = [];
        $hasParcelCol = rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id');

        foreach ($parsed['deltas'] as $delta) {
            $variant = (string) $delta['variant'];
            $size = (string) $delta['size'];
            $qty = (float) $delta['qty'];
            $used = honey_pos_deduct_packaging_aggregate($pdo, $variant, $size, $qty);
            foreach ($used as $row) {
                $allLines[] = [
                    'packaging_row_id' => (int) $row['packaging_row_id'],
                    'variant' => $variant,
                    'size' => $size,
                    'qty' => (float) $row['qty'],
                ];
                $st = $pdo->prepare(
                    'INSERT INTO online_parcel_deduct_lines (parcel_id, packaging_row_id, variant, size, qty)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $st->execute([$parcelId, (int) $row['packaging_row_id'], $variant, $size, (float) $row['qty']]);
            }

            honey_parcel_log_sale($pdo, $parcelId, $userId, $variant, $size, $qty, $hasParcelCol);
        }

        foreach ($deptItems as $productKey => $qty) {
            $deducted = dept_inventory_deduct($pdo, $productKey, (float) $qty);
            $stDept = $pdo->prepare(
                'INSERT INTO online_parcel_dept_deduct_lines (parcel_id, dept_row_id, product_key, qty)
                 VALUES (?, ?, ?, ?)'
            );
            $stDept->execute([$parcelId, $deducted['dept_row_id'], $productKey, $deducted['qty']]);
            $deptDeducted[] = ['product_key' => $productKey, 'qty' => $deducted['qty']];
        }

        $fulfillmentDeducted = parcel_fulfillment_apply_deduct($pdo, $parcelId, $userId, $itemsRaw);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'deducted' => $parsed['deltas'],
        'dept_deducted' => $deptDeducted,
        'lines' => $allLines,
        'fulfillment_deducted' => $fulfillmentDeducted,
    ];
}

/**
 * Restore packaging + dept stock when a ship is reversed.
 */
function honey_parcel_revert_deduct(PDO $pdo, int $parcelId): void
{
    if ($parcelId < 1) {
        return;
    }

    $st = $pdo->prepare(
        'SELECT packaging_row_id, size, qty FROM online_parcel_deduct_lines WHERE parcel_id = ?'
    );
    $st->execute([$parcelId]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($lines as $line) {
        $size = (string) ($line['size'] ?? '');
        $qty = (float) ($line['qty'] ?? 0);
        $rowId = (int) ($line['packaging_row_id'] ?? 0);
        if ($rowId < 1 || $qty <= 0 || $size === '') {
            continue;
        }
        [$curCol] = honey_packaging_size_columns($size);
        $pdo->prepare(
            "UPDATE honey_packaging_rows SET {$curCol} = {$curCol} + ? WHERE id = ?"
        )->execute([$qty, $rowId]);
    }

    if ($lines !== []) {
        $pdo->prepare('DELETE FROM online_parcel_deduct_lines WHERE parcel_id = ?')->execute([$parcelId]);
    }

    $stDept = $pdo->prepare(
        'SELECT dept_row_id, qty FROM online_parcel_dept_deduct_lines WHERE parcel_id = ?'
    );
    $stDept->execute([$parcelId]);
    $deptLines = $stDept->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($deptLines as $line) {
        $rowId = (int) ($line['dept_row_id'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        if ($rowId < 1 || $qty <= 0) {
            continue;
        }
        $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = current_qty + ? WHERE id = ?')
            ->execute([$qty, $rowId]);
    }
    if ($deptLines !== []) {
        $pdo->prepare('DELETE FROM online_parcel_dept_deduct_lines WHERE parcel_id = ?')->execute([$parcelId]);
    }

    parcel_fulfillment_revert_deduct($pdo, $parcelId);

    if (rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id')) {
        $pdo->prepare(
            'DELETE FROM honey_sales_log WHERE online_parcel_id = ? AND sale_type = "parcel"'
        )->execute([$parcelId]);
    }
}

function honey_parcel_log_sale(
    PDO $pdo,
    int $parcelId,
    int $userId,
    string $variant,
    string $size,
    float $qty,
    bool $hasParcelCol
): void {
    $variant = honey_normalize_variant($variant);
    $qtyCols = ['liter' => 0.0, '250ml' => 0.0, '350ml' => 0.0, 'gallon' => 0.0];
    if (!isset($qtyCols[$size])) {
        throw new RuntimeException('Invalid size.');
    }
    $qtyCols[$size] = $qty;

    if ($hasParcelCol) {
        $ins = $pdo->prepare(
            'INSERT INTO honey_sales_log (
                source, packaging_row_id, batch_no, variant,
                qty_liter, qty_250ml, qty_350ml, qty_gallon,
                sale_type, sold_by, online_parcel_id
             ) VALUES ("packaging", NULL, NULL, ?, ?, ?, ?, ?, "parcel", ?, ?)'
        );
        $ins->execute([
            $variant,
            $qtyCols['liter'],
            $qtyCols['250ml'],
            $qtyCols['350ml'],
            $qtyCols['gallon'],
            $userId,
            $parcelId,
        ]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO honey_sales_log (
                source, packaging_row_id, batch_no, variant,
                qty_liter, qty_250ml, qty_350ml, qty_gallon,
                sale_type, sold_by
             ) VALUES ("packaging", NULL, NULL, ?, ?, ?, ?, ?, "parcel", ?)'
        );
        $ins->execute([
            $variant,
            $qtyCols['liter'],
            $qtyCols['250ml'],
            $qtyCols['350ml'],
            $qtyCols['gallon'],
            $userId,
        ]);
    }
}
