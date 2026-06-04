<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/rts_schema.php';

/**
 * Apply packaging deduct for a shipped online parcel (aggregate FIFO).
 *
 * @return array{deducted: list<array{variant: string, size: string, qty: float}>, lines: list<array{packaging_row_id: int, variant: string, size: string, qty: float}>}
 */
function honey_parcel_apply_deduct(PDO $pdo, int $parcelId, int $userId, string $itemsRaw): array
{
    $parsed = honey_parcel_parse_items_string($itemsRaw);
    if ($parsed['errors'] !== []) {
        throw new RuntimeException(implode(' ', $parsed['errors']));
    }
    if ($parsed['deltas'] === []) {
        throw new RuntimeException('No inventory lines to deduct for this parcel.');
    }

    $pdo->beginTransaction();
    try {
        $allLines = [];
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

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['deducted' => $parsed['deltas'], 'lines' => $allLines];
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
