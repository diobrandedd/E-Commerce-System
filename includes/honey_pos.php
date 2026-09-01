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

function honey_pos_line_unit_ids(array $entry): array
{
    $ids = [];
    if (isset($entry['bottle_unit_id']) && (int) $entry['bottle_unit_id'] > 0) {
        $ids[] = (int) $entry['bottle_unit_id'];
    }
    if (isset($entry['bottle_unit_ids']) && is_array($entry['bottle_unit_ids'])) {
        foreach ($entry['bottle_unit_ids'] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }

    return array_values(array_unique($ids));
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
    require_once __DIR__ . '/pos_sales_helpers.php';
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }
    if (!$items) {
        throw new RuntimeException('Cart is empty.');
    }

    $buyerName = pos_normalize_buyer_name($buyerName);
    $sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));
    $seller = pos_sales_seller_insert($pdo, ['pin_id' => $posPinId, 'user_id' => null], $sellerName);

    $discountPercent = isset($opts['discount_percent']) ? (int) $opts['discount_percent'] : 0;
    if (!in_array($discountPercent, [0, 10, 20], true)) {
        throw new RuntimeException('Invalid discount percent.');
    }
    $discountFactor = $discountPercent > 0 ? (1 - ($discountPercent / 100)) : 1.0;
    $discountRequestId = isset($opts['discount_request_id']) ? (int) $opts['discount_request_id'] : null;
    $stockSource = strtolower(trim((string) ($opts['stock_source'] ?? 'packaging')));
    if (!in_array($stockSource, ['packaging', 'rts'], true)) {
        throw new RuntimeException('Invalid stock source.');
    }
    if ($stockSource === 'rts') {
        require_once __DIR__ . '/rts_inventory_sync.php';
    }

    $productMap = honey_pos_product_map($pdo);
    $cleanLines = [];
    $subtotal = 0.0;

    foreach ($items as $entry) {
        $productId = (int) ($entry['product_id'] ?? 0);
        $qty = (float) ($entry['qty'] ?? 0);
        $variant = strtolower(trim((string) ($entry['variant'] ?? '')));
        $size = trim((string) ($entry['size'] ?? ''));
        $unitIds = honey_pos_line_unit_ids($entry);
        if ($unitIds !== []) {
            $qty = (float) count($unitIds);
        }

        if ($productId < 1 || $qty <= 0 || $variant === '' || $size === '') {
            throw new RuntimeException('Invalid cart line.');
        }

        $prod = $productMap[$variant][$size] ?? null;
        if (!$prod || (int) $prod['product_id'] !== $productId) {
            throw new RuntimeException('Product not found or mismatch.');
        }

        if ($unitIds === []) {
            if ($stockSource === 'rts') {
                require_once __DIR__ . '/rts_inventory_sync.php';
                if (!rts_sellable_from_cases_enabled($pdo)) {
                    throw new RuntimeException(
                        'RTS walk-in must scan product barcodes (qty-only Use RTS is disabled).'
                    );
                }
            }
            $avail = $stockSource === 'rts'
                ? rts_inventory_available_honey($pdo, $variant, $size, 'sellable')
                : honey_pos_available_qty($pdo, $variant, $size);
            if ($qty > $avail + 0.0001) {
                $poolLabel = $stockSource === 'rts' ? 'RTS' : 'on hand';
                throw new RuntimeException(
                    'Not enough ' . ucfirst($variant) . ' ' . (HONEY_POS_SIZE_LABELS[$size] ?? $size) . " {$poolLabel} ({$avail} available)."
                );
            }
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
            'bottle_unit_ids' => $unitIds,
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
                discount_percent, discount_amount, total_amount, ' . $seller['sql_cols'] . ', sale_date
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ' . $seller['sql_vals'] . ', NOW())'
        );
        $insSale->execute(array_merge([
            $receiptNo,
            $buyerName,
            $buyerCreds === '' ? null : $buyerCreds,
            $subtotal,
            $discountPercent > 0 ? $discountPercent : null,
            $discountAmount,
            $total,
        ], $seller['params']));
        $saleId = (int) $pdo->lastInsertId();

        $hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');

        foreach ($cleanLines as $line) {
            $unitIds = $line['bottle_unit_ids'] ?? [];
            if ($unitIds !== []) {
                require_once __DIR__ . '/honey_bottle_barcodes.php';
                honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);
            } elseif ($stockSource === 'rts') {
                rts_inventory_deduct_sell_honey_variant(
                    $pdo,
                    0,
                    $line['variant'],
                    [$line['size'] => $line['qty']],
                    'walk_in',
                    'sellable',
                    [
                        'used_where' => 'pos',
                        'pos_sale_id' => $saleId,
                        'actor_user_id' => null,
                    ]
                );
                honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);
            } else {
                honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);
                honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);
            }

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
        'stock_source' => $stockSource,
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
        'buyer_name' => $buyerName,
        'seller_name' => $sellerName,
    ];
}

/**
 * AR checkout — full price, inventory deduct, pos_sales + ar_credits.
 *
 * @param list<array{product_id: int, qty: float, variant?: string, size?: string}> $items
 * @param array{debtor_user_id?: int|null, debtor_name: string, debtor_mobile?: string|null} $debtor
 * @param array{stock_source?: string} $opts
 * @return array<string, mixed>
 */
function honey_pos_ar_checkout(PDO $pdo, int $posPinId, array $items, array $debtor, array $opts = []): array
{
    require_once __DIR__ . '/pos_sales_helpers.php';
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }
    if (!$items) {
        throw new RuntimeException('Cart is empty.');
    }
    if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {
        throw new RuntimeException('AR checkout requires migration phase 50 (pos_sales.is_ar).');
    }

    $sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));
    $seller = pos_sales_seller_insert($pdo, ['pin_id' => $posPinId, 'user_id' => null], $sellerName);

    $debtorName = trim((string) ($debtor['debtor_name'] ?? ''));
    if ($debtorName === '') {
        throw new RuntimeException('Debtor name is required.');
    }
    $debtorUserId = isset($debtor['debtor_user_id']) ? (int) $debtor['debtor_user_id'] : null;
    if ($debtorUserId !== null && $debtorUserId < 1) {
        $debtorUserId = null;
    }
    $debtorMobile = trim((string) ($debtor['debtor_mobile'] ?? ''));
    $stockSource = strtolower(trim((string) ($opts['stock_source'] ?? 'packaging')));
    if (!in_array($stockSource, ['packaging', 'rts'], true)) {
        throw new RuntimeException('Invalid stock source.');
    }
    if ($stockSource === 'rts') {
        require_once __DIR__ . '/rts_inventory_sync.php';
    }

    $productMap = honey_pos_product_map($pdo);
    $cleanLines = [];
    $subtotal = 0.0;

    foreach ($items as $entry) {
        $productId = (int) ($entry['product_id'] ?? 0);
        $qty = (float) ($entry['qty'] ?? 0);
        $variant = strtolower(trim((string) ($entry['variant'] ?? '')));
        $size = trim((string) ($entry['size'] ?? ''));
        $unitIds = honey_pos_line_unit_ids($entry);
        if ($unitIds !== []) {
            $qty = (float) count($unitIds);
        }

        if ($productId < 1 || $qty <= 0 || $variant === '' || $size === '') {
            throw new RuntimeException('Invalid cart line.');
        }

        $prod = $productMap[$variant][$size] ?? null;
        if (!$prod || (int) $prod['product_id'] !== $productId) {
            throw new RuntimeException('Product not found or mismatch.');
        }

        if ($unitIds === []) {
            if ($stockSource === 'rts') {
                require_once __DIR__ . '/rts_inventory_sync.php';
                if (!rts_sellable_from_cases_enabled($pdo)) {
                    throw new RuntimeException(
                        'RTS walk-in must scan product barcodes (qty-only Use RTS is disabled).'
                    );
                }
            }
            $avail = $stockSource === 'rts'
                ? rts_inventory_available_honey($pdo, $variant, $size, 'sellable')
                : honey_pos_available_qty($pdo, $variant, $size);
            if ($qty > $avail + 0.0001) {
                $poolLabel = $stockSource === 'rts' ? 'RTS' : 'on hand';
                throw new RuntimeException(
                    'Not enough ' . ucfirst($variant) . ' ' . (HONEY_POS_SIZE_LABELS[$size] ?? $size) . " {$poolLabel} ({$avail} available)."
                );
            }
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
            'bottle_unit_ids' => $unitIds,
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
                discount_percent, discount_amount, total_amount, is_ar, ' . $seller['sql_cols'] . ', sale_date
             ) VALUES (?, ?, ?, ?, NULL, 0, ?, 1, ' . $seller['sql_vals'] . ', NOW())'
        );
        $insSale->execute(array_merge([
            $receiptNo,
            $debtorName,
            $debtorMobile === '' ? null : $debtorMobile,
            $subtotal,
            $total,
        ], $seller['params']));
        $saleId = (int) $pdo->lastInsertId();

        $hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');
        $arLines = [];

        foreach ($cleanLines as $line) {
            $unitIds = $line['bottle_unit_ids'] ?? [];
            if ($unitIds !== []) {
                require_once __DIR__ . '/honey_bottle_barcodes.php';
                honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);
            } elseif ($stockSource === 'rts') {
                rts_inventory_deduct_sell_honey_variant(
                    $pdo,
                    0,
                    $line['variant'],
                    [$line['size'] => $line['qty']],
                    'walk_in',
                    'sellable',
                    [
                        'used_where' => 'pos',
                        'pos_sale_id' => $saleId,
                        'actor_user_id' => null,
                    ]
                );
                honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);
            } else {
                honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);
                honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);
            }

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
        'stock_source' => $stockSource,
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

/**
 * Recalculate pos_sales money fields from remaining receipt lines.
 * Caller should already be in a transaction when mutating lines.
 */
function pos_sale_recalc_totals(PDO $pdo, int $saleId): void
{
    $saleId = (int) $saleId;
    if ($saleId < 1) {
        return;
    }
    $hasList = honey_pos_column_exists($pdo, 'pos_receipt_items', 'list_unit_price');
    $st = $pdo->prepare(
        'SELECT qty, unit_price, line_total'
        . ($hasList ? ', list_unit_price' : '')
        . ' FROM pos_receipt_items WHERE sale_id = ?'
    );
    $st->execute([$saleId]);
    $subtotal = 0.0;
    $total = 0.0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $qty = (float) ($row['qty'] ?? 0);
        $list = $hasList
            ? (float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0)
            : (float) ($row['unit_price'] ?? 0);
        $subtotal += round($list * $qty, 2);
        $total += round((float) ($row['line_total'] ?? 0), 2);
    }
    $subtotal = round($subtotal, 2);
    $total = round($total, 2);
    $discountAmount = round(max(0, $subtotal - $total), 2);
    $hasSub = honey_pos_column_exists($pdo, 'pos_sales', 'subtotal_amount');
    $hasDiscAmt = honey_pos_column_exists($pdo, 'pos_sales', 'discount_amount');
    if ($hasSub && $hasDiscAmt) {
        $pdo->prepare(
            'UPDATE pos_sales
             SET subtotal_amount = ?, discount_amount = ?, total_amount = ?
             WHERE id = ?'
        )->execute([$subtotal, $discountAmount, $total, $saleId]);
    } else {
        $pdo->prepare('UPDATE pos_sales SET total_amount = ? WHERE id = ?')
            ->execute([$total, $saleId]);
    }
}

/**
 * Remove one unit from a POS receipt (qty−1 or delete line) and recalc totals.
 *
 * @param array{variant?: string, size?: string, product_key?: string} $match
 */
function pos_sale_reverse_one_unit(PDO $pdo, int $saleId, string $kind, array $match): void
{
    $saleId = (int) $saleId;
    if ($saleId < 1) {
        return;
    }
    $kind = strtolower(trim($kind));
    $hasVariant = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');
    $hasList = honey_pos_column_exists($pdo, 'pos_receipt_items', 'list_unit_price');

    $lock = $pdo->prepare('SELECT id FROM pos_sales WHERE id = ? FOR UPDATE');
    $lock->execute([$saleId]);
    if ($lock->fetchColumn() === false) {
        return;
    }

    if ($kind === 'honey' && $hasVariant) {
        $variant = strtolower(trim((string) ($match['variant'] ?? '')));
        $size = trim((string) ($match['size'] ?? ''));
        if ($variant === '' || $size === '') {
            return;
        }
        $st = $pdo->prepare(
            'SELECT id, qty, unit_price'
            . ($hasList ? ', list_unit_price' : '')
            . ' FROM pos_receipt_items
             WHERE sale_id = ? AND LOWER(TRIM(variant)) = ? AND size = ? AND qty >= 1
             ORDER BY id DESC LIMIT 1 FOR UPDATE'
        );
        $st->execute([$saleId, $variant, $size]);
    } elseif ($kind === 'dept') {
        $productKey = strtolower(trim((string) ($match['product_key'] ?? '')));
        if ($productKey === '') {
            return;
        }
        $st = $pdo->prepare(
            'SELECT pri.id, pri.qty, pri.unit_price'
            . ($hasList ? ', pri.list_unit_price' : '')
            . ' FROM pos_receipt_items pri
             INNER JOIN products p ON p.id = pri.product_id
             WHERE pri.sale_id = ? AND LOWER(TRIM(p.dept_product_key)) = ? AND pri.qty >= 1
             ORDER BY pri.id DESC LIMIT 1 FOR UPDATE'
        );
        $st->execute([$saleId, $productKey]);
    } else {
        return;
    }

    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return;
    }
    $lineId = (int) $row['id'];
    $qty = (float) $row['qty'];
    $unitPrice = (float) $row['unit_price'];
    $listUnit = $hasList ? (float) ($row['list_unit_price'] ?? $unitPrice) : $unitPrice;

    if ($qty <= 1.0001) {
        $pdo->prepare('DELETE FROM pos_receipt_items WHERE id = ? AND sale_id = ?')
            ->execute([$lineId, $saleId]);
    } else {
        $newQty = round($qty - 1, 2);
        $lineTotal = round($unitPrice * $newQty, 2);
        if ($hasList) {
            $pdo->prepare(
                'UPDATE pos_receipt_items SET qty = ?, line_total = ? WHERE id = ? AND sale_id = ?'
            )->execute([$newQty, $lineTotal, $lineId, $saleId]);
        } else {
            $pdo->prepare(
                'UPDATE pos_receipt_items SET qty = ?, line_total = ? WHERE id = ? AND sale_id = ?'
            )->execute([$newQty, $lineTotal, $lineId, $saleId]);
        }
        unset($listUnit); // kept for clarity / future list_line sync
    }

    pos_sale_recalc_totals($pdo, $saleId);
}

/**
 * Resolve POS sale id for a sold honey bottle unit.
 */
function pos_sale_id_for_honey_unit(PDO $pdo, int $unitId, ?int $consumedRefId = null): int
{
    if ($consumedRefId !== null && $consumedRefId > 0) {
        return $consumedRefId;
    }
    require_once __DIR__ . '/rts_schema.php';
    if (!rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id')
        || !rts_db_has_column($pdo, 'honey_sales_log', 'pos_sale_id')) {
        return 0;
    }
    $st = $pdo->prepare(
        'SELECT pos_sale_id FROM honey_sales_log
         WHERE bottle_unit_id = ? AND pos_sale_id IS NOT NULL
           AND (COALESCE(qty_liter,0)+COALESCE(qty_250ml,0)+COALESCE(qty_350ml,0)+COALESCE(qty_gallon,0)) > 0
         ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$unitId]);
    return (int) ($st->fetchColumn() ?: 0);
}

/**
 * Heal receipt money when units were restocked before totals were reduced.
 * Idempotent: only reverses while receipt qty exceeds still-sold barcode count.
 */
function pos_sale_heal_returned_units(PDO $pdo, int $saleId): void
{
    $saleId = (int) $saleId;
    if ($saleId < 1) {
        return;
    }
    require_once __DIR__ . '/rts_schema.php';

    $activeHoney = [];
    $returnedHoney = [];
    if (rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id')) {
        $st = $pdo->prepare(
            'SELECT u.variant, u.size, u.status,
                    (COALESCE(h.qty_liter,0)+COALESCE(h.qty_250ml,0)+COALESCE(h.qty_350ml,0)+COALESCE(h.qty_gallon,0)) AS qty_signed
             FROM honey_sales_log h
             INNER JOIN honey_bottle_units u ON u.id = h.bottle_unit_id
             WHERE h.pos_sale_id = ? AND h.bottle_unit_id IS NOT NULL'
        );
        $st->execute([$saleId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ((float) ($row['qty_signed'] ?? 0) <= 0) {
                continue;
            }
            $key = strtolower(trim((string) $row['variant'])) . '|' . trim((string) $row['size']);
            if ((string) $row['status'] === 'sold') {
                $activeHoney[$key] = ($activeHoney[$key] ?? 0) + 1;
            } else {
                $returnedHoney[$key] = ($returnedHoney[$key] ?? 0) + 1;
            }
        }
    }

    $hasVariant = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');
    if ($hasVariant && $returnedHoney !== []) {
        $st = $pdo->prepare(
            'SELECT LOWER(TRIM(variant)) AS variant, size, SUM(qty) AS qty
             FROM pos_receipt_items
             WHERE sale_id = ? AND variant IS NOT NULL AND variant != ""
             GROUP BY LOWER(TRIM(variant)), size'
        );
        $st->execute([$saleId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['variant'] . '|' . trim((string) $row['size']);
            $receiptQty = (int) round((float) $row['qty']);
            $active = $activeHoney[$key] ?? 0;
            $extra = $receiptQty - $active;
            if ($extra < 1) {
                continue;
            }
            // Only heal up to known returned count for this key.
            $extra = min($extra, $returnedHoney[$key] ?? $extra);
            [$variant, $size] = explode('|', $key, 2);
            for ($i = 0; $i < $extra; $i++) {
                pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [
                    'variant' => $variant,
                    'size' => $size,
                ]);
            }
        }
    }

    $st = $pdo->prepare(
        'SELECT un.product_key, un.status
         FROM dept_pouch_units un
         WHERE un.consumed_ref_type = "pos_sale" AND un.consumed_ref_id = ?'
    );
    // Dept returned units lose consumed_ref — heal dept from remaining sold links vs receipt only when we still have sold refs.
    // For dept returns after clear, match via nothing reliable without sales log; skip dept heal here.
}

