<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/sku_barcodes.php';
require_once __DIR__ . '/ar_credits.php';
require_once __DIR__ . '/api_tokens.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/dept_inventory.php';

/**
 * Credit packaging aggregate for a honey size. Prefers the unit's packaging row;
 * otherwise the newest packaging row for that variant. Never touches RTS sellable.
 */
function pos_refund_credit_honey_packaging(
    PDO $pdo,
    string $variant,
    string $size,
    float $qty,
    int $preferredPackagingRowId = 0
): int {
    $variant = honey_normalize_variant($variant);
    $qty = (float) $qty;
    if ($qty < 1e-9 || !in_array($size, HONEY_SIZES, true)) {
        return 0;
    }
    if ($preferredPackagingRowId > 0) {
        honey_bottle_adjust_aggregate($pdo, $preferredPackagingRowId, $size, $qty);
        return $preferredPackagingRowId;
    }
    $st = $pdo->prepare(
        'SELECT id FROM honey_packaging_rows WHERE variant = ? ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $st->execute([$variant]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return 0;
    }
    $packId = (int) $row['id'];
    honey_bottle_adjust_aggregate($pdo, $packId, $size, $qty);

    return $packId;
}

/**
 * Full-sale refund/void for mobile POS.
 * Restocks packaging / finished inventory only (POS never sells from RTS).
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
        $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
        $hasDeptPool = rts_db_has_column($pdo, 'dept_pouch_units', 'pool');

        // Restock unique honey bottles → packaging inventory (never RTS).
        $honeySt = $pdo->prepare(
            'SELECT u.id, u.variant, u.size, u.packaging_row_id, u.status, pk.batch_no
             FROM honey_bottle_units u
             LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
             WHERE u.consumed_ref_type = "pos_sale" AND u.consumed_ref_id = ? AND u.status = "sold"
             FOR UPDATE'
        );
        $honeySt->execute([$saleId]);
        foreach ($honeySt->fetchAll(PDO::FETCH_ASSOC) as $unit) {
            $unitId = (int) $unit['id'];
            $variant = honey_normalize_variant((string) $unit['variant']);
            $size = (string) $unit['size'];
            $upd = $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "in_stock", pool = "packaging",
                     consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status = "sold"'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                continue;
            }
            $packId = pos_refund_credit_honey_packaging(
                $pdo,
                $variant,
                $size,
                1.0,
                (int) ($unit['packaging_row_id'] ?? 0)
            );
            pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [
                'variant' => $variant,
                'size' => $size,
            ]);
            honey_bottle_write_sale_log($pdo, [
                'source' => 'packaging',
                'packaging_row_id' => $packId > 0 ? $packId : null,
                'batch_no' => $unit['batch_no'] !== null ? (string) $unit['batch_no'] : null,
                'variant' => $variant,
                'size' => $size,
                'qty' => -1,
                'sale_type' => 'walk_in',
                'sold_by' => null,
                'pos_sale_id' => $saleId,
                'bottle_unit_id' => $hasUnitCol ? $unitId : null,
            ]);
            $restocked++;
        }

        // Restock unique dept pouches/jars → finished inventory (never RTS).
        $deptSt = $pdo->prepare(
            'SELECT un.id, un.product_key, un.status
             FROM dept_pouch_units un
             WHERE un.consumed_ref_type = "pos_sale" AND un.consumed_ref_id = ? AND un.status = "sold"
             FOR UPDATE'
        );
        $deptSt->execute([$saleId]);
        foreach ($deptSt->fetchAll(PDO::FETCH_ASSOC) as $unit) {
            $unitId = (int) $unit['id'];
            $productKey = (string) $unit['product_key'];
            if ($hasDeptPool) {
                $upd = $pdo->prepare(
                    'UPDATE dept_pouch_units
                     SET status = "in_stock", pool = "finished",
                         consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                     WHERE id = ? AND status = "sold"'
                );
            } else {
                $upd = $pdo->prepare(
                    'UPDATE dept_pouch_units
                     SET status = "in_stock",
                         consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                     WHERE id = ? AND status = "sold"'
                );
            }
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                continue;
            }
            $fin = dept_inventory_fetch_row($pdo, $productKey, true);
            if ($fin !== null) {
                $pdo->prepare(
                    'UPDATE dept_inventory_rows SET current_qty = current_qty + 1 WHERE id = ?'
                )->execute([(int) $fin['id']]);
            } else {
                $fin = dept_inventory_ensure_row($pdo, 0, $productKey);
                $pdo->prepare(
                    'UPDATE dept_inventory_rows SET current_qty = current_qty + 1 WHERE id = ?'
                )->execute([(int) $fin['id']]);
            }
            pos_sale_reverse_one_unit($pdo, $saleId, 'dept', [
                'product_key' => $productKey,
            ]);
            $restocked++;
        }

        // Remaining honey SKU qty lines on the receipt → packaging (never RTS).
        // pos_receipt_items has variant/size; honey_sales_log does not (uses qty_* columns).
        if (honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant')
            && honey_pos_column_exists($pdo, 'pos_receipt_items', 'size')) {
            $skuHoney = $pdo->prepare(
                'SELECT id, variant, size, qty
                 FROM pos_receipt_items
                 WHERE sale_id = ? AND variant IS NOT NULL AND TRIM(variant) <> ""
                   AND size IS NOT NULL AND TRIM(size) <> "" AND qty > 0
                 FOR UPDATE'
            );
            $skuHoney->execute([$saleId]);
            foreach ($skuHoney->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $variant = honey_normalize_variant((string) $line['variant']);
                $size = trim((string) $line['size']);
                $qtyLeft = (int) floor((float) $line['qty']);
                if ($qtyLeft < 1 || !in_array($size, HONEY_SIZES, true)) {
                    continue;
                }
                $packId = pos_refund_credit_honey_packaging($pdo, $variant, $size, (float) $qtyLeft, 0);
                honey_bottle_write_sale_log($pdo, [
                    'source' => 'packaging',
                    'packaging_row_id' => $packId > 0 ? $packId : null,
                    'batch_no' => null,
                    'variant' => $variant,
                    'size' => $size,
                    'qty' => -$qtyLeft,
                    'sale_type' => 'walk_in',
                    'sold_by' => null,
                    'pos_sale_id' => $saleId,
                ]);
                $restocked += $qtyLeft;
            }
        }

        // Remaining dept SKU qty lines (no unique unit left) → finished inventory.
        $skuDept = $pdo->prepare(
            'SELECT pri.id, pri.qty, p.dept_product_key
             FROM pos_receipt_items pri
             INNER JOIN products p ON p.id = pri.product_id
             WHERE pri.sale_id = ? AND p.dept_product_key IS NOT NULL AND TRIM(p.dept_product_key) <> ""
               AND pri.qty > 0
               AND (pri.variant IS NULL OR TRIM(pri.variant) = "")
             FOR UPDATE'
        );
        try {
            $skuDept->execute([$saleId]);
            foreach ($skuDept->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $productKey = strtolower(trim((string) $line['dept_product_key']));
                $qtyLeft = (int) floor((float) $line['qty']);
                if ($productKey === '' || $qtyLeft < 1) {
                    continue;
                }
                $fin = dept_inventory_ensure_row($pdo, 0, $productKey);
                $pdo->prepare(
                    'UPDATE dept_inventory_rows SET current_qty = current_qty + ? WHERE id = ?'
                )->execute([$qtyLeft, (int) $fin['id']]);
                $restocked += $qtyLeft;
            }
        } catch (Throwable) {
            // products.dept_product_key may be missing on older DBs — unique units already handled.
        }

        // Clear receipt lines and zero the sale.
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
