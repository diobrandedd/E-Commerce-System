<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/ar_credits.php';

/** POS cashier variants (excludes "other"). */
const HONEY_POS_VARIANTS = ['light', 'amber', 'dark'];

const HONEY_POS_SIZE_LABELS = [
    '250ml' => '250ml',
    '350ml' => '350ml',
    'liter' => '1L',
    'gallon' => 'Gallon',
];

function honey_pos_variant_image_url(string $variant): string
{
    $v = strtolower(trim($variant));
    if (!in_array($v, HONEY_POS_VARIANTS, true)) {
        $v = 'light';
    }

    return '/rts/assets/sys_img/' . $v . '.png';
}

function honey_pos_resolve_honey_dept_id(PDO $pdo): int
{
    static $deptId = null;
    if ($deptId !== null) {
        return $deptId;
    }
    foreach (['Honey', 'honey', 'HONEY'] as $name) {
        $st = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $st->execute([$name]);
        $row = $st->fetch();
        if ($row) {
            $deptId = (int) $row['id'];

            return $deptId;
        }
    }
    $st = $pdo->query("SELECT id FROM departments WHERE LOWER(name) LIKE '%honey%' LIMIT 1");
    $row = $st ? $st->fetch() : false;
    if (!$row) {
        throw new RuntimeException('Honey department not found in departments table.');
    }
    $deptId = (int) $row['id'];

    return $deptId;
}

/** @return array<string, float> variant => total bottles */
function honey_pos_packaging_availability(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT variant,
                COALESCE(SUM(current_liter), 0) AS liter,
                COALESCE(SUM(current_250ml), 0) AS ml250,
                COALESCE(SUM(current_350ml), 0) AS ml350,
                COALESCE(SUM(current_gallon), 0) AS gallon
         FROM honey_packaging_rows
         GROUP BY variant'
    );
    $byVariant = [];
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $v = strtolower((string) $row['variant']);
        $byVariant[$v] = [
            'liter' => (float) $row['liter'],
            '250ml' => (float) $row['ml250'],
            '350ml' => (float) $row['ml350'],
            'gallon' => (float) $row['gallon'],
        ];
    }

    return $byVariant;
}

/** @return array<string, array<string, array<string, mixed>>> */
function honey_pos_product_map(PDO $pdo): array
{
    $deptId = honey_pos_resolve_honey_dept_id($pdo);
    $st = $pdo->prepare(
        'SELECT id, name, size, variant, selling_price
         FROM products
         WHERE dept_id = ? AND selling_price > 0 AND variant IS NOT NULL AND size IS NOT NULL'
    );
    $st->execute([$deptId]);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $v = strtolower((string) $row['variant']);
        $s = (string) $row['size'];
        $map[$v][$s] = [
            'product_id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'unit_price' => round((float) $row['selling_price'], 2),
        ];
    }

    return $map;
}

/** @return array{variants: list<array<string, mixed>>} */
function honey_pos_honey_catalog(PDO $pdo): array
{
    $availability = honey_pos_packaging_availability($pdo);
    $products = honey_pos_product_map($pdo);
    $variants = [];

    foreach (HONEY_POS_VARIANTS as $variant) {
        $avail = $availability[$variant] ?? ['liter' => 0, '250ml' => 0, '350ml' => 0, 'gallon' => 0];
        $sizes = [];
        $totalOnHand = 0.0;

        foreach (HONEY_SIZES as $size) {
            $availableQty = (float) ($avail[$size] ?? 0);
            $totalOnHand += $availableQty;
            $prod = $products[$variant][$size] ?? null;
            $sizes[] = [
                'size' => $size,
                'label' => HONEY_POS_SIZE_LABELS[$size] ?? $size,
                'available_qty' => $availableQty,
                'product_id' => $prod ? $prod['product_id'] : null,
                'unit_price' => $prod ? $prod['unit_price'] : null,
                'sellable' => $prod !== null && $prod['unit_price'] > 0,
            ];
        }

        $variants[] = [
            'variant' => $variant,
            'image' => honey_pos_variant_image_url($variant),
            'total_on_hand' => $totalOnHand,
            'sizes' => $sizes,
        ];
    }

    return ['variants' => $variants];
}

function honey_pos_available_qty(PDO $pdo, string $variant, string $size): float
{
    $variant = honey_normalize_variant($variant);
    [$curCol] = honey_packaging_size_columns($size);
    $st = $pdo->prepare(
        "SELECT COALESCE(SUM({$curCol}), 0) AS qty FROM honey_packaging_rows WHERE variant = ?"
    );
    $st->execute([$variant]);

    return (float) ($st->fetchColumn() ?: 0);
}

/**
 * Deduct qty from packaging rows (FIFO by bottling_date) without exposing batch to cashier.
 *
 * @return list<array{packaging_row_id: int, qty: float}>
 */
function honey_pos_deduct_packaging_aggregate(PDO $pdo, string $variant, string $size, float $qty): array
{
    $variant = honey_normalize_variant($variant);
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be positive.');
    }
    [$curCol] = honey_packaging_size_columns($size);
    $available = honey_pos_available_qty($pdo, $variant, $size);
    if ($qty > $available + 0.0001) {
        $label = ucfirst($variant) . ' ' . (HONEY_POS_SIZE_LABELS[$size] ?? $size);
        throw new RuntimeException("Not enough {$label} on hand ({$available} available).");
    }

    $st = $pdo->prepare(
        "SELECT id, {$curCol} AS cur FROM honey_packaging_rows
         WHERE variant = ? AND {$curCol} > 0
         ORDER BY bottling_date ASC, id ASC"
    );
    $st->execute([$variant]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $remaining = $qty;
    $used = [];
    foreach ($rows as $row) {
        if ($remaining <= 0) {
            break;
        }
        $rowId = (int) $row['id'];
        $onHand = (float) $row['cur'];
        if ($onHand <= 0) {
            continue;
        }
        $take = min($remaining, $onHand);
        $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = {$curCol} - ? WHERE id = ?")->execute([$take, $rowId]);
        $used[] = ['packaging_row_id' => $rowId, 'qty' => $take];
        $remaining -= $take;
    }

    if ($remaining > 0.0001) {
        throw new RuntimeException('Could not complete stock deduction.');
    }

    return $used;
}

function honey_pos_log_sale(
    PDO $pdo,
    string $variant,
    string $size,
    float $qty,
    int $posSaleId,
    int $posPinId
): int {
    $variant = honey_normalize_variant($variant);
    $qtyCols = ['liter' => 0, '250ml' => 0, '350ml' => 0, 'gallon' => 0];
    if (!isset($qtyCols[$size])) {
        throw new RuntimeException('Invalid size.');
    }
    $qtyCols[$size] = $qty;

    $hasPinCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'sold_by_pin_id');
    $hasSaleCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'pos_sale_id');

    if ($hasPinCol && $hasSaleCol) {
        $ins = $pdo->prepare(
            'INSERT INTO honey_sales_log (
                source, packaging_row_id, batch_no, variant,
                qty_liter, qty_250ml, qty_350ml, qty_gallon,
                sale_type, sold_by, sold_by_pin_id, pos_sale_id
             ) VALUES ("packaging", NULL, NULL, ?, ?, ?, ?, ?, "walk_in", NULL, ?, ?)'
        );
        $ins->execute([
            $variant,
            $qtyCols['liter'],
            $qtyCols['250ml'],
            $qtyCols['350ml'],
            $qtyCols['gallon'],
            $posPinId,
            $posSaleId,
        ]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO honey_sales_log (
                source, packaging_row_id, batch_no, variant,
                qty_liter, qty_250ml, qty_350ml, qty_gallon,
                sale_type, sold_by
             ) VALUES ("packaging", NULL, NULL, ?, ?, ?, ?, ?, "walk_in", ?)'
        );
        $ins->execute([
            $variant,
            $qtyCols['liter'],
            $qtyCols['250ml'],
            $qtyCols['350ml'],
            $qtyCols['gallon'],
            1,
        ]);
    }

    return (int) $pdo->lastInsertId();
}

function honey_pos_column_exists(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $st->execute([$table, $column]);

    return (bool) $st->fetchColumn();
}

/**
 * @param list<array{product_id: int, qty: float, variant?: string, size?: string}> $items
 * @param array{discount_percent?: int|float|string|null, discount_request_id?: int|null} $opts
 * @return array<string, mixed>
 */
function honey_pos_checkout(PDO $pdo, int $posPinId, array $items, string $buyerName, string $buyerCreds, array $opts = []): array
{
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }
    if (!$items) {
        throw new RuntimeException('Cart is empty.');
    }

    $discountPercent = isset($opts['discount_percent']) ? (int) $opts['discount_percent'] : 0;
    if (!in_array($discountPercent, [0, 10, 20], true)) {
        throw new RuntimeException('Invalid discount percent.');
    }
    $discountFactor = $discountPercent > 0 ? (1 - ($discountPercent / 100)) : 1.0;
    $discountRequestId = isset($opts['discount_request_id']) ? (int) $opts['discount_request_id'] : null;

    $productMap = honey_pos_product_map($pdo);
    $cleanLines = [];
    $subtotal = 0.0;

    foreach ($items as $entry) {
        $productId = (int) ($entry['product_id'] ?? 0);
        $qty = (float) ($entry['qty'] ?? 0);
        $variant = strtolower(trim((string) ($entry['variant'] ?? '')));
        $size = trim((string) ($entry['size'] ?? ''));

        if ($productId < 1 || $qty <= 0 || $variant === '' || $size === '') {
            throw new RuntimeException('Invalid cart line.');
        }

        $prod = $productMap[$variant][$size] ?? null;
        if (!$prod || (int) $prod['product_id'] !== $productId) {
            throw new RuntimeException('Product not found or mismatch.');
        }

        $avail = honey_pos_available_qty($pdo, $variant, $size);
        if ($qty > $avail + 0.0001) {
            throw new RuntimeException(
                'Not enough ' . ucfirst($variant) . ' ' . (HONEY_POS_SIZE_LABELS[$size] ?? $size) . " on hand ({$avail} available)."
            );
        }

        $listUnit = (float) $prod['unit_price'];
        $chargedUnit = round($listUnit * $discountFactor, 2);
        $lineSubtotal = round($listUnit * $qty, 2);
        $lineTotal = round($chargedUnit * $qty, 2);
        $subtotal += $lineSubtotal;
        $cleanLines[] = [
            'product_id' => $productId,
            'variant' => $variant,
            'size' => $size,
            'qty' => $qty,
            'list_unit_price' => $listUnit,
            'unit_price' => $chargedUnit,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineTotal,
            'name' => $prod['name'],
        ];
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $subtotal = round($subtotal, 2);
        $total = round(array_sum(array_column($cleanLines, 'line_total')), 2);
        $discountAmount = round($subtotal - $total, 2);
        $receiptNo = sprintf('RCP-%s', strtoupper(bin2hex(random_bytes(4))));

        $insSale = $pdo->prepare(
            'INSERT INTO pos_sales (
                receipt_no, buyer_name, buyer_credentials, subtotal_amount,
                discount_percent, discount_amount, total_amount, sold_by_pin_id, sale_date
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $insSale->execute([
            $receiptNo,
            $buyerName !== '' ? $buyerName : 'Walk-in',
            $buyerCreds === '' ? null : $buyerCreds,
            $subtotal,
            $discountPercent > 0 ? $discountPercent : null,
            $discountAmount,
            $total,
            $posPinId,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');

        foreach ($cleanLines as $line) {
            honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);
            honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);

            if ($hasVariantCol) {
                $insLine = $pdo->prepare(
                    'INSERT INTO pos_receipt_items (
                        sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent, variant, size
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insLine->execute([
                    $saleId,
                    $line['product_id'],
                    $line['qty'],
                    $line['unit_price'],
                    $line['list_unit_price'],
                    $line['line_total'],
                    $discountPercent > 0 ? $discountPercent : null,
                    $line['variant'],
                    $line['size'],
                ]);
            } else {
                $insLine = $pdo->prepare(
                    'INSERT INTO pos_receipt_items (
                        sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent
                     ) VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $insLine->execute([
                    $saleId,
                    $line['product_id'],
                    $line['qty'],
                    $line['unit_price'],
                    $line['list_unit_price'],
                    $line['line_total'],
                    $discountPercent > 0 ? $discountPercent : null,
                ]);
            }
        }

        if ($discountRequestId !== null && $discountRequestId > 0) {
            $updReq = $pdo->prepare(
                'UPDATE pos_discount_requests
                 SET status = "approved", pos_sale_id = ?, reviewed_at = COALESCE(reviewed_at, NOW())
                 WHERE id = ?'
            );
            $updReq->execute([$saleId, $discountRequestId]);
        }

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
        'sale_id' => $saleId,
        'receipt_no' => $receiptNo,
        'subtotal' => $subtotal,
        'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
        'discount_amount' => $discountAmount,
        'total' => $total,
        'items' => array_map(static fn ($l) => [
            'product_id' => $l['product_id'],
            'qty' => $l['qty'],
            'list_unit_price' => $l['list_unit_price'],
            'unit_price' => $l['unit_price'],
            'line_subtotal' => $l['line_subtotal'],
            'line_total' => $l['line_total'],
            'name' => $l['name'],
            'variant' => $l['variant'],
            'size' => $l['size'],
        ], $cleanLines),
        'buyer_name' => $buyerName !== '' ? $buyerName : 'Walk-in',
    ];
}

/**
 * AR checkout — full price, inventory deduct, pos_sales + ar_credits.
 *
 * @param list<array{product_id: int, qty: float, variant?: string, size?: string}> $items
 * @param array{debtor_user_id?: int|null, debtor_name: string, debtor_mobile?: string|null} $debtor
 * @return array<string, mixed>
 */
function honey_pos_ar_checkout(PDO $pdo, int $posPinId, array $items, array $debtor): array
{
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }
    if (!$items) {
        throw new RuntimeException('Cart is empty.');
    }
    if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {
        throw new RuntimeException('AR checkout requires migration phase 50 (pos_sales.is_ar).');
    }

    $debtorName = trim((string) ($debtor['debtor_name'] ?? ''));
    if ($debtorName === '') {
        throw new RuntimeException('Debtor name is required.');
    }
    $debtorUserId = isset($debtor['debtor_user_id']) ? (int) $debtor['debtor_user_id'] : null;
    if ($debtorUserId !== null && $debtorUserId < 1) {
        $debtorUserId = null;
    }
    $debtorMobile = trim((string) ($debtor['debtor_mobile'] ?? ''));

    $productMap = honey_pos_product_map($pdo);
    $cleanLines = [];
    $subtotal = 0.0;

    foreach ($items as $entry) {
        $productId = (int) ($entry['product_id'] ?? 0);
        $qty = (float) ($entry['qty'] ?? 0);
        $variant = strtolower(trim((string) ($entry['variant'] ?? '')));
        $size = trim((string) ($entry['size'] ?? ''));

        if ($productId < 1 || $qty <= 0 || $variant === '' || $size === '') {
            throw new RuntimeException('Invalid cart line.');
        }

        $prod = $productMap[$variant][$size] ?? null;
        if (!$prod || (int) $prod['product_id'] !== $productId) {
            throw new RuntimeException('Product not found or mismatch.');
        }

        $avail = honey_pos_available_qty($pdo, $variant, $size);
        if ($qty > $avail + 0.0001) {
            throw new RuntimeException(
                'Not enough ' . ucfirst($variant) . ' ' . (HONEY_POS_SIZE_LABELS[$size] ?? $size) . " on hand ({$avail} available)."
            );
        }

        $listUnit = (float) $prod['unit_price'];
        $lineTotal = round($listUnit * $qty, 2);
        $subtotal += $lineTotal;
        $cleanLines[] = [
            'product_id' => $productId,
            'variant' => $variant,
            'size' => $size,
            'qty' => $qty,
            'list_unit_price' => $listUnit,
            'unit_price' => $listUnit,
            'line_subtotal' => $lineTotal,
            'line_total' => $lineTotal,
            'name' => $prod['name'],
        ];
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $subtotal = round($subtotal, 2);
        $total = $subtotal;
        $receiptNo = sprintf('AR-RCP-%s', strtoupper(bin2hex(random_bytes(4))));

        $insSale = $pdo->prepare(
            'INSERT INTO pos_sales (
                receipt_no, buyer_name, buyer_credentials, subtotal_amount,
                discount_percent, discount_amount, total_amount, is_ar, sold_by_pin_id, sale_date
             ) VALUES (?, ?, ?, ?, NULL, 0, ?, 1, ?, NOW())'
        );
        $insSale->execute([
            $receiptNo,
            $debtorName,
            $debtorMobile === '' ? null : $debtorMobile,
            $subtotal,
            $total,
            $posPinId,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');
        $arLines = [];

        foreach ($cleanLines as $line) {
            honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);
            honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);

            if ($hasVariantCol) {
                $insLine = $pdo->prepare(
                    'INSERT INTO pos_receipt_items (
                        sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent, variant, size
                     ) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?)'
                );
                $insLine->execute([
                    $saleId,
                    $line['product_id'],
                    $line['qty'],
                    $line['unit_price'],
                    $line['list_unit_price'],
                    $line['line_total'],
                    $line['variant'],
                    $line['size'],
                ]);
            } else {
                $insLine = $pdo->prepare(
                    'INSERT INTO pos_receipt_items (
                        sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent
                     ) VALUES (?, ?, ?, ?, ?, ?, NULL)'
                );
                $insLine->execute([
                    $saleId,
                    $line['product_id'],
                    $line['qty'],
                    $line['unit_price'],
                    $line['list_unit_price'],
                    $line['line_total'],
                ]);
            }

            $arLines[] = [
                'product_id' => $line['product_id'],
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'],
            ];
        }

        $arCreditIds = ar_credits_record_lines($pdo, [
            'debtor_user_id' => $debtorUserId,
            'debtor_name' => $debtorName,
            'debtor_mobile' => $debtorMobile,
            'source' => 'pos',
            'recorded_by_pin_id' => $posPinId,
            'pos_sale_id' => $saleId,
        ], $arLines);

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
        'sale_id' => $saleId,
        'receipt_no' => $receiptNo,
        'subtotal' => $subtotal,
        'total' => $total,
        'ar_credit_ids' => $arCreditIds,
        'items' => array_map(static fn ($l) => [
            'product_id' => $l['product_id'],
            'qty' => $l['qty'],
            'unit_price' => $l['unit_price'],
            'line_total' => $l['line_total'],
            'name' => $l['name'],
            'variant' => $l['variant'],
            'size' => $l['size'],
        ], $cleanLines),
        'debtor_name' => $debtorName,
        'debtor_mobile' => $debtorMobile !== '' ? $debtorMobile : null,
    ];
}
