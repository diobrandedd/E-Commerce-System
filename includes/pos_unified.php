<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/dept_pos.php';

/**
 * Current POS cashier: PIN terminal or IMS user with pos.sell.
 *
 * @return array{pin_id: ?int, user_id: ?int}
 */
function pos_session_actor(): array
{
    if (ims_pos_authenticated()) {
        $pin = (int) ($_SESSION['pos_pin_id'] ?? 0);
        if ($pin < 1) {
            throw new RuntimeException('POS session is invalid.');
        }

        return ['pin_id' => $pin, 'user_id' => null];
    }
    $user = ims_current_user();
    if ($user === null || (int) $user['id'] < 1) {
        throw new RuntimeException('POS session is invalid.');
    }

    return ['pin_id' => null, 'user_id' => (int) $user['id']];
}

/**
 * @param array{pin_id?: ?int, user_id?: ?int} $actor
 * @return array{sql_cols: string, sql_vals: string, params: list<mixed>}
 */
function pos_sales_seller_insert(PDO $pdo, array $actor): array
{
    $pin = isset($actor['pin_id']) ? (int) $actor['pin_id'] : 0;
    $user = isset($actor['user_id']) ? (int) $actor['user_id'] : 0;
    $pinVal = $pin > 0 ? $pin : null;
    $userVal = $user > 0 ? $user : null;
    if ($pinVal === null && $userVal === null) {
        throw new RuntimeException('POS session is invalid.');
    }
    if (honey_pos_column_exists($pdo, 'pos_sales', 'sold_by_user_id')) {
        return [
            'sql_cols' => 'sold_by_pin_id, sold_by_user_id',
            'sql_vals' => '?, ?',
            'params' => [$pinVal, $userVal],
        ];
    }
    if ($pinVal === null) {
        throw new RuntimeException('Run sql/migration_phase65_pos_sold_by_user.sql so account POS can record the seller.');
    }

    return [
        'sql_cols' => 'sold_by_pin_id',
        'sql_vals' => '?',
        'params' => [$pinVal],
    ];
}

/**
 * @return array{product_id: int, name: string, suggested_price: float|null}|null
 */
function pos_unified_resolve_honey_product(PDO $pdo, string $variant, string $size): ?array
{
    $deptId = honey_pos_resolve_honey_dept_id($pdo);
    $st = $pdo->prepare(
        'SELECT id, name, selling_price
         FROM products
         WHERE dept_id = ? AND LOWER(variant) = ? AND size = ?
         LIMIT 1'
    );
    $st->execute([$deptId, strtolower(trim($variant)), trim($size)]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    $price = $row['selling_price'] !== null ? round((float) $row['selling_price'], 2) : null;

    return [
        'product_id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'suggested_price' => $price !== null && $price > 0 ? $price : null,
    ];
}

/**
 * @return array{product_id: int, name: string, suggested_price: float|null}|null
 */
function pos_unified_resolve_dept_product(PDO $pdo, string $productKey): ?array
{
    $productKey = strtolower(trim($productKey));
    $st = $pdo->prepare(
        'SELECT id, name, selling_price
         FROM products
         WHERE dept_product_key = ?
         LIMIT 1'
    );
    $st->execute([$productKey]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        $def = dept_product_by_key($productKey);

        return [
            'product_id' => 0,
            'name' => (string) ($def['label'] ?? $productKey),
            'suggested_price' => null,
        ];
    }
    $price = $row['selling_price'] !== null ? round((float) $row['selling_price'], 2) : null;

    return [
        'product_id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'suggested_price' => $price !== null && $price > 0 ? $price : null,
    ];
}

/**
 * @return array<string, mixed>
 */
function pos_unified_lookup(PDO $pdo, string $code): array
{
    $code = trim($code);
    if ($code === '') {
        throw new RuntimeException('Scan a barcode.');
    }

    try {
        $honey = honey_bottle_pos_lookup($pdo, $code);
        $variant = (string) $honey['variant'];
        $size = (string) $honey['size'];
        $prod = pos_unified_resolve_honey_product($pdo, $variant, $size);
        if ($prod === null || $prod['product_id'] < 1) {
            throw new RuntimeException('No product record for this honey bottle.');
        }

        return [
            'kind' => 'honey',
            'label' => trim(
                ucfirst($variant) . ' · ' . honey_bottle_size_label($size)
            ),
            'product_id' => $prod['product_id'],
            'product_name' => $prod['name'],
            'variant' => $variant,
            'size' => $size,
            'size_label' => honey_bottle_size_label($size),
            'product_key' => null,
            'unit_id' => (int) $honey['unit_id'],
            'code' => (string) $honey['code'],
            'batch_no' => (string) $honey['batch_no'],
            'suggested_price' => $prod['suggested_price'],
        ];
    } catch (RuntimeException $honeyErr) {
        if (!str_contains(strtolower($honeyErr->getMessage()), 'unknown barcode')) {
            throw $honeyErr;
        }
    }

    $dept = dept_pouch_pos_lookup($pdo, $code);
    $prod = pos_unified_resolve_dept_product($pdo, (string) $dept['product_key']);
    if ($prod === null || $prod['product_id'] < 1) {
        throw new RuntimeException('No product record for ' . (string) $dept['product_label'] . '.');
    }

    return [
        'kind' => 'dept',
        'label' => (string) $dept['product_label'],
        'product_id' => $prod['product_id'],
        'product_name' => $prod['name'],
        'variant' => null,
        'size' => null,
        'size_label' => null,
        'product_key' => (string) $dept['product_key'],
        'unit_id' => (int) $dept['unit_id'],
        'code' => (string) $dept['code'],
        'batch_no' => (string) $dept['batch_no'],
        'suggested_price' => $prod['suggested_price'],
    ];
}

/**
 * @param list<array<string,mixed>> $items
 * @param float $discountFactor
 * @return list<array<string,mixed>>
 */
function pos_unified_build_cart_lines(PDO $pdo, array $items, float $discountFactor = 1.0): array
{
    if ($items === []) {
        throw new RuntimeException('Cart is empty.');
    }
    if ($discountFactor <= 0 || $discountFactor > 1.0001) {
        throw new RuntimeException('Invalid discount factor.');
    }

    $clean = [];
    foreach ($items as $entry) {
        $kind = strtolower(trim((string) ($entry['kind'] ?? '')));
        if (!in_array($kind, ['honey', 'dept'], true)) {
            throw new RuntimeException('Invalid cart line kind.');
        }
        $productId = (int) ($entry['product_id'] ?? 0);
        $listUnit = round((float) ($entry['list_unit_price'] ?? 0), 2);
        if ($productId < 1 || $listUnit <= 0) {
            throw new RuntimeException('Enter a price for each scanned item.');
        }

        $unitIds = [];
        if ($kind === 'honey') {
            $unitIds = honey_pos_line_unit_ids($entry);
            $variant = strtolower(trim((string) ($entry['variant'] ?? '')));
            $size = trim((string) ($entry['size'] ?? ''));
            if ($unitIds === [] || $variant === '' || $size === '') {
                throw new RuntimeException('Invalid honey cart line.');
            }
            $prod = pos_unified_resolve_honey_product($pdo, $variant, $size);
            if ($prod === null || (int) $prod['product_id'] !== $productId) {
                throw new RuntimeException('Product mismatch.');
            }
            foreach ($unitIds as $uid) {
                $ust = $pdo->prepare(
                    'SELECT status, variant, size FROM honey_bottle_units WHERE id = ? LIMIT 1'
                );
                $ust->execute([(int) $uid]);
                $urow = $ust->fetch(PDO::FETCH_ASSOC);
                if ($urow === false) {
                    throw new RuntimeException('Scanned bottle not found.');
                }
                if ((string) $urow['status'] !== 'in_stock') {
                    throw new RuntimeException('A scanned bottle is not available.');
                }
                if (strtolower((string) $urow['variant']) !== $variant || (string) $urow['size'] !== $size) {
                    throw new RuntimeException('Scanned bottle product mismatch.');
                }
            }
            $qty = (float) count($unitIds);
            $name = (string) $prod['name'];
            $line = [
                'kind' => 'honey',
                'product_id' => $productId,
                'variant' => $variant,
                'size' => $size,
                'qty' => $qty,
                'list_unit_price' => $listUnit,
                'unit_price' => round($listUnit * $discountFactor, 2),
                'line_subtotal' => round($listUnit * $qty, 2),
                'name' => $name,
                'bottle_unit_ids' => $unitIds,
            ];
        } else {
            $unitIds = dept_pos_pouch_unit_ids_from_entry($entry);
            $productKey = strtolower(trim((string) ($entry['product_key'] ?? '')));
            if ($unitIds === [] || $productKey === '') {
                throw new RuntimeException('Invalid dept cart line.');
            }
            $prod = pos_unified_resolve_dept_product($pdo, $productKey);
            if ($prod === null || (int) $prod['product_id'] !== $productId) {
                throw new RuntimeException('Product mismatch.');
            }
            foreach ($unitIds as $uid) {
                $ust = $pdo->prepare('SELECT product_key, status FROM dept_pouch_units WHERE id = ? LIMIT 1');
                $ust->execute([(int) $uid]);
                $urow = $ust->fetch(PDO::FETCH_ASSOC);
                if ($urow === false) {
                    throw new RuntimeException('Scanned unit not found.');
                }
                if ((string) $urow['product_key'] !== $productKey) {
                    throw new RuntimeException('Scanned unit product mismatch.');
                }
                if ((string) $urow['status'] !== 'in_stock') {
                    throw new RuntimeException('A scanned unit is not available.');
                }
            }
            $qty = (float) count($unitIds);
            $line = [
                'kind' => 'dept',
                'product_id' => $productId,
                'product_key' => $productKey,
                'qty' => $qty,
                'list_unit_price' => $listUnit,
                'unit_price' => round($listUnit * $discountFactor, 2),
                'line_subtotal' => round($listUnit * $qty, 2),
                'name' => (string) $prod['name'],
                'pouch_unit_ids' => $unitIds,
            ];
        }

        $line['line_total'] = round($line['unit_price'] * $line['qty'], 2);
        $clean[] = $line;
    }

    return $clean;
}

/**
 * @param array{pin_id?: ?int, user_id?: ?int} $actor
 * @param list<array<string,mixed>> $items
 * @param array{discount_percent?: int|null, discount_request_id?: int|null} $opts
 * @return array<string, mixed>
 */
function pos_unified_checkout(PDO $pdo, array $actor, array $items, string $buyerName, string $buyerCreds, array $opts = []): array
{
    $seller = pos_sales_seller_insert($pdo, $actor);

    $discountPercent = isset($opts['discount_percent']) ? (int) $opts['discount_percent'] : 0;
    if (!in_array($discountPercent, [0, 10, 20], true)) {
        throw new RuntimeException('Invalid discount percent.');
    }
    $discountFactor = $discountPercent > 0 ? (1 - ($discountPercent / 100)) : 1.0;
    $discountRequestId = isset($opts['discount_request_id']) ? (int) $opts['discount_request_id'] : null;

    $cleanLines = pos_unified_build_cart_lines($pdo, $items, $discountFactor);
    $subtotal = round(array_sum(array_column($cleanLines, 'line_subtotal')), 2);
    $total = round(array_sum(array_column($cleanLines, 'line_total')), 2);
    $discountAmount = round($subtotal - $total, 2);

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $receiptNo = sprintf('RCP-%s', strtoupper(bin2hex(random_bytes(4))));
        $insSale = $pdo->prepare(
            'INSERT INTO pos_sales (
                receipt_no, buyer_name, buyer_credentials, subtotal_amount,
                discount_percent, discount_amount, total_amount, ' . $seller['sql_cols'] . ', sale_date
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ' . $seller['sql_vals'] . ', NOW())'
        );
        $insSale->execute(array_merge([
            $receiptNo,
            $buyerName !== '' ? $buyerName : 'Walk-in',
            $buyerCreds === '' ? null : $buyerCreds,
            $subtotal,
            $discountPercent > 0 ? $discountPercent : null,
            $discountAmount,
            $total,
        ], $seller['params']));
        $saleId = (int) $pdo->lastInsertId();

        $hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');

        foreach ($cleanLines as $line) {
            if ($line['kind'] === 'honey') {
                honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);
            } else {
                dept_pouch_pos_consume($pdo, $line['pouch_unit_ids'], $saleId);
            }

            if ($hasVariantCol && $line['kind'] === 'honey') {
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
                    'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
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
            $pdo->prepare(
                'UPDATE pos_discount_requests
                 SET status = "approved", pos_sale_id = ?, reviewed_at = COALESCE(reviewed_at, NOW())
                 WHERE id = ?'
            )->execute([$saleId, $discountRequestId]);
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
        'receipt_no' => $receiptNo,
        'subtotal' => $subtotal,
        'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
        'discount_amount' => $discountAmount,
        'total' => $total,
        'buyer_name' => $buyerName !== '' ? $buyerName : 'Walk-in',
        'items' => array_map(static fn ($l) => [
            'product_id' => $l['product_id'],
            'qty' => $l['qty'],
            'list_unit_price' => $l['list_unit_price'],
            'unit_price' => $l['unit_price'],
            'line_subtotal' => $l['line_subtotal'],
            'line_total' => $l['line_total'],
            'name' => $l['name'],
            'variant' => $l['variant'] ?? null,
            'size' => $l['size'] ?? null,
            'kind' => $l['kind'],
            'product_key' => $l['product_key'] ?? null,
        ], $cleanLines),
    ];
}

/**
 * @param array{pin_id?: ?int, user_id?: ?int} $actor
 * @param list<array<string,mixed>> $items
 * @param array{debtor_user_id?: int|null, debtor_name: string, debtor_mobile?: string|null} $debtor
 * @return array<string, mixed>
 */
function pos_unified_ar_checkout(PDO $pdo, array $actor, array $items, array $debtor): array
{
    $seller = pos_sales_seller_insert($pdo, $actor);
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

    $cleanLines = pos_unified_build_cart_lines($pdo, $items, 1.0);
    $total = round(array_sum(array_column($cleanLines, 'line_total')), 2);

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
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
            $debtorMobile !== '' ? $debtorMobile : null,
            $total,
            $total,
        ], $seller['params']));
        $saleId = (int) $pdo->lastInsertId();

        $hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');
        $arLines = [];

        foreach ($cleanLines as $line) {
            if ($line['kind'] === 'honey') {
                honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);
            } else {
                dept_pouch_pos_consume($pdo, $line['pouch_unit_ids'], $saleId);
            }

            if ($hasVariantCol && $line['kind'] === 'honey') {
                $pdo->prepare(
                    'INSERT INTO pos_receipt_items (
                        sale_id, product_id, qty, unit_price, list_unit_price, line_total, variant, size
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
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
                $pdo->prepare(
                    'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, list_unit_price, line_total)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
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

        $creditIds = ar_credits_record_lines($pdo, [
            'debtor_user_id' => $debtorUserId,
            'debtor_name' => $debtorName,
            'debtor_mobile' => $debtorMobile,
            'source' => 'pos',
            'recorded_by_pin_id' => $posPinId,
            'pos_sale_id' => $saleId,
        ], $arLines);

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
        'receipt_no' => $receiptNo,
        'total' => $total,
        'debtor_name' => $debtorName,
        'debtor_mobile' => $debtorMobile !== '' ? $debtorMobile : null,
        'ar_credit_ids' => $creditIds,
    ];
}

/**
 * @param list<array<string,mixed>> $items
 * @return array{items: list<array<string,mixed>>, subtotal: float, discount_percent: int, discount_amount: float, total: float}
 */
function pos_unified_discount_quote(PDO $pdo, array $items, int $discountPercent): array
{
    if (!in_array($discountPercent, [10, 20], true)) {
        throw new RuntimeException('Discount must be 10% or 20%.');
    }
    $discountFactor = 1 - ($discountPercent / 100);
    $clean = pos_unified_build_cart_lines($pdo, $items, $discountFactor);
    $subtotal = round(array_sum(array_column($clean, 'line_subtotal')), 2);
    $total = round(array_sum(array_column($clean, 'line_total')), 2);

    return [
        'items' => $clean,
        'subtotal' => $subtotal,
        'discount_percent' => $discountPercent,
        'discount_amount' => round($subtotal - $total, 2),
        'total' => $total,
    ];
}

/** @param list<array<string,mixed>> $items */
function pos_discount_cart_is_unified(array $items): bool
{
    if ($items === []) {
        return false;
    }
    foreach ($items as $entry) {
        $kind = strtolower(trim((string) ($entry['kind'] ?? '')));
        if ($kind === 'honey' || $kind === 'dept') {
            return true;
        }
    }

    return false;
}
