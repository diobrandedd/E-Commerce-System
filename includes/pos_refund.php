<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/sku_barcodes.php';
require_once __DIR__ . '/ar_credits.php';
require_once __DIR__ . '/api_tokens.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/rts_inventory_sync.php';
require_once __DIR__ . '/dept_inventory.php';

/**
 * Full-sale refund/void for mobile POS (and website-compatible DB updates).
 *
 * @return array{sale_id:int,receipt_no:string,refunded:bool,restocked_units:int}
 */
function pos_sale_full_refund(PDO $pdo, int $saleId, string $reason = ''): array
{
    $saleId = (int) $saleId;
    if ($saleId < 1) {
        throw new RuntimeException('Invalid sale.');
    }
    pos_sales_ensure_refund_columns($pdo);
    $reason = substr(trim($reason), 0, 255);

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare('SELECT * FROM pos_sales WHERE id = ? FOR UPDATE');
        $st->execute([$saleId]);
        $sale = $st->fetch(PDO::FETCH_ASSOC);
        if ($sale === false) {
            throw new RuntimeException('Sale not found.');
        }
        if (!empty($sale['refunded_at'])) {
            throw new RuntimeException('Sale is already refunded.');
        }

        $restocked = 0;

        // Restock unique honey bottles sold on this receipt.
        $honeySt = $pdo->prepare(
            'SELECT u.id, u.variant, u.size, u.pool, u.packaging_row_id, u.status
             FROM honey_bottle_units u
             WHERE u.consumed_ref_type = "pos_sale" AND u.consumed_ref_id = ? AND u.status = "sold"
             FOR UPDATE'
        );
        $honeySt->execute([$saleId]);
        $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
        foreach ($honeySt->fetchAll(PDO::FETCH_ASSOC) as $unit) {
            $unitId = (int) $unit['id'];
            $variant = honey_normalize_variant((string) $unit['variant']);
            $size = (string) $unit['size'];
            $pool = (string) ($unit['pool'] ?? 'packaging');
            $upd = $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "in_stock", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status = "sold"'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                continue;
            }
            $packId = (int) ($unit['packaging_row_id'] ?? 0);
            if ($pool === 'rts_sellable') {
                rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);
            } elseif ($packId > 0) {
                honey_bottle_adjust_aggregate($pdo, $packId, $size, 1.0);
            }
            pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [
                'variant' => $variant,
                'size' => $size,
            ]);
            honey_bottle_write_sale_log($pdo, [
                'source' => $pool === 'rts_sellable' ? 'rts' : 'packaging',
                'packaging_row_id' => $packId > 0 ? $packId : null,
                'variant' => $variant,
                'size' => $size,
                'qty' => -1,
                'sale_type' => 'walk_in',
                'sold_by' => 0,
                'pos_sale_id' => $saleId,
                'bottle_unit_id' => $hasUnitCol ? $unitId : null,
            ]);
            $restocked++;
        }

        // Restock unique dept pouches/jars.
        $deptSt = $pdo->prepare(
            'SELECT un.id, un.product_key, un.pool, un.status
             FROM dept_pouch_units un
             WHERE un.consumed_ref_type = "pos_sale" AND un.consumed_ref_id = ? AND un.status = "sold"
             FOR UPDATE'
        );
        $deptSt->execute([$saleId]);
        foreach ($deptSt->fetchAll(PDO::FETCH_ASSOC) as $unit) {
            $unitId = (int) $unit['id'];
            $productKey = (string) $unit['product_key'];
            $pool = (string) ($unit['pool'] ?? 'finished');
            $upd = $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = "in_stock", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status = "sold"'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                continue;
            }
            if ($pool === 'rts_sellable') {
                rts_inventory_credit_sellable_other($pdo, dept_pouch_product_label($productKey), 1.0);
            } else {
                require_once __DIR__ . '/dept_inventory.php';
                $fin = dept_inventory_fetch_row($pdo, $productKey, true);
                if ($fin !== null) {
                    $pdo->prepare(
                        'UPDATE dept_inventory_rows SET current_qty = current_qty + 1 WHERE id = ?'
                    )->execute([(int) $fin['id']]);
                }
            }
            pos_sale_reverse_one_unit($pdo, $saleId, 'dept', [
                'product_key' => $productKey,
            ]);
            $restocked++;
        }

        // Remaining receipt lines (SKU / qty sells): credit inventory from sales log then clear lines.
        $logSt = $pdo->prepare(
            'SELECT variant, size, source, packaging_row_id, SUM(qty) AS qty_sum
             FROM honey_sales_log
             WHERE pos_sale_id = ? AND sale_type = "walk_in"
             GROUP BY variant, size, source, packaging_row_id
             HAVING SUM(qty) > 0'
        );
        $logSt->execute([$saleId]);
        foreach ($logSt->fetchAll(PDO::FETCH_ASSOC) as $log) {
            $qtyLeft = (int) floor((float) $log['qty_sum']);
            if ($qtyLeft < 1) {
                continue;
            }
            // Unique-unit restocks already wrote negative log rows; only credit leftover SKU qty.
            $variant = honey_normalize_variant((string) ($log['variant'] ?? ''));
            $size = (string) ($log['size'] ?? '');
            if ($variant === '' || $size === '') {
                continue;
            }
            $pool = ((string) ($log['source'] ?? '') === 'rts') ? 'rts_sellable' : 'packaging';
            $packId = $log['packaging_row_id'] !== null ? (int) $log['packaging_row_id'] : null;
            try {
                sku_barcode_honey_credit($pdo, $variant, $size, $qtyLeft, $pool, $packId);
                for ($i = 0; $i < $qtyLeft; $i++) {
                    pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [
                        'variant' => $variant,
                        'size' => $size,
                    ]);
                }
                honey_bottle_write_sale_log($pdo, [
                    'source' => $pool === 'rts_sellable' ? 'rts' : 'packaging',
                    'packaging_row_id' => $packId,
                    'variant' => $variant,
                    'size' => $size,
                    'qty' => -$qtyLeft,
                    'sale_type' => 'walk_in',
                    'sold_by' => 0,
                    'pos_sale_id' => $saleId,
                ]);
                $restocked += $qtyLeft;
            } catch (Throwable) {
                // If SKU credit fails (missing pack id), still clear money below.
            }
        }

        // Clear any remaining receipt item qty/money.
        $pdo->prepare('DELETE FROM pos_receipt_items WHERE sale_id = ?')->execute([$saleId]);
        if (honey_pos_column_exists($pdo, 'pos_sales', 'subtotal_amount')) {
            $pdo->prepare(
                'UPDATE pos_sales
                 SET subtotal_amount = 0, discount_amount = 0, total_amount = 0,
                     refunded_at = NOW(), refund_reason = ?
                 WHERE id = ?'
            )->execute([$reason !== '' ? $reason : null, $saleId]);
        } else {
            $pdo->prepare(
                'UPDATE pos_sales
                 SET total_amount = 0, refunded_at = NOW(), refund_reason = ?
                 WHERE id = ?'
            )->execute([$reason !== '' ? $reason : null, $saleId]);
        }

        // Cancel outstanding AR credits for this sale (paid credits block refund).
        $arCheck = $pdo->prepare(
            'SELECT id, status FROM ar_credits WHERE pos_sale_id = ? FOR UPDATE'
        );
        $arCheck->execute([$saleId]);
        foreach ($arCheck->fetchAll(PDO::FETCH_ASSOC) as $credit) {
            if ((string) $credit['status'] === 'paid') {
                throw new RuntimeException('Cannot refund: AR credit already marked paid. Mark unpaid first.');
            }
            $pdo->prepare('DELETE FROM ar_credits WHERE id = ? AND status = "outstanding"')
                ->execute([(int) $credit['id']]);
        }

        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'sale_id' => $saleId,
        'receipt_no' => (string) $sale['receipt_no'],
        'refunded' => true,
        'restocked_units' => $restocked,
    ];
}
