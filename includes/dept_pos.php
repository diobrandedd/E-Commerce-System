<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/ar_credits.php';
require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';

/**
 * @param array<string, mixed> $entry
 * @return list<int>
 */
function dept_pos_pouch_unit_ids_from_entry(array $entry): array
{
    $ids = [];
    if (isset($entry['pouch_unit_id']) && (int) $entry['pouch_unit_id'] > 0) {
        $ids[] = (int) $entry['pouch_unit_id'];
    }
    if (isset($entry['pouch_unit_ids']) && is_array($entry['pouch_unit_ids'])) {
        foreach ($entry['pouch_unit_ids'] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }

    return array_values(array_unique($ids));
}

/** @return array{products: list<array<string, mixed>>} */
function dept_pos_catalog(PDO $pdo, ?string $deptKey = null): array
{
    $products = [];
    foreach (dept_inventory_catalog_snapshot($pdo, $deptKey) as $item) {
        $sellable = $item['product_id'] !== null
            && $item['selling_price'] !== null
            && (float) $item['selling_price'] > 0;
        $products[] = array_merge($item, [
            'sellable' => $sellable,
            'available_qty' => (float) $item['current_qty'],
        ]);
    }

    return ['products' => $products];
}

/**
 * @param list<array{product_key: string, product_id?: int, qty: float}> $items
 * @param float $discountFactor 1.0 for full price, 0.9 for 10% off, etc.
 * @param string $stockSource packaging|rts
 * @return list<array<string, mixed>>
 */
function dept_pos_build_cart_lines(PDO $pdo, array $items, float $discountFactor = 1.0, string $stockSource = 'packaging'): array
{
    if ($items === []) {
        throw new RuntimeException('Cart is empty.');
    }
    if ($discountFactor <= 0 || $discountFactor > 1.0001) {
        throw new RuntimeException('Invalid discount factor.');
    }
    if (!in_array($stockSource, ['packaging', 'rts'], true)) {
        throw new RuntimeException('Invalid stock source.');
    }
    if ($stockSource === 'rts') {
        require_once __DIR__ . '/rts_inventory_sync.php';
    }

    $snapshot = dept_inventory_catalog_snapshot($pdo);
    $onHandByKey = [];
    foreach ($snapshot as $s) {
        $onHandByKey[$s['key']] = (float) $s['current_qty'];
    }

    $cleanLines = [];
    foreach ($items as $entry) {
        $productKey = trim((string) ($entry['product_key'] ?? ''));
        $qty = (float) ($entry['qty'] ?? 0);
        $expectedProductId = isset($entry['product_id']) ? (int) $entry['product_id'] : 0;
        if (!dept_product_key_valid($productKey) || $qty <= 0) {
            throw new RuntimeException('Invalid cart line.');
        }

        $unitIds = dept_pos_pouch_unit_ids_from_entry($entry);
        if (dept_pouch_supports($productKey) && $stockSource === 'packaging') {
            if ($unitIds === []) {
                throw new RuntimeException('Scan each sticker into the cart — quantity-only sell is disabled.');
            }
            if (count($unitIds) !== (int) round($qty)) {
                throw new RuntimeException('Scanned unit count must match quantity.');
            }
            foreach ($unitIds as $uid) {
                $ust = $pdo->prepare(
                    'SELECT product_key, status FROM dept_pouch_units WHERE id = ? LIMIT 1'
                );
                $ust->execute([$uid]);
                $urow = $ust->fetch(PDO::FETCH_ASSOC);
                if ($urow === false) {
                    throw new RuntimeException('Scanned unit not found.');
                }
                if ((string) $urow['product_key'] !== $productKey) {
                    throw new RuntimeException('Scanned unit product mismatch.');
                }
                if ((string) $urow['status'] !== 'in_stock') {
                    throw new RuntimeException('A scanned unit is not available (' . (string) $urow['status'] . ').');
                }
            }
        } elseif (dept_pouch_supports($productKey) && $stockSource === 'rts' && $unitIds === []) {
            require_once __DIR__ . '/rts_inventory_sync.php';
            if (!rts_sellable_from_cases_enabled($pdo)) {
                throw new RuntimeException(
                    'RTS walk-in must scan product barcodes (qty-only Use RTS is disabled).'
                );
            }
        }

        $st = $pdo->prepare(
            'SELECT p.id, p.name, p.selling_price
             FROM products p
             WHERE p.dept_product_key = ? AND p.selling_price > 0
             LIMIT 1'
        );
        $st->execute([$productKey]);
        $prod = $st->fetch(PDO::FETCH_ASSOC);
        if (!$prod) {
            $def = dept_product_by_key($productKey);
            throw new RuntimeException(($def['label'] ?? $productKey) . ' is not sellable yet (set selling price).');
        }

        $productId = (int) $prod['id'];
        if ($expectedProductId > 0 && $expectedProductId !== $productId) {
            throw new RuntimeException('Product not found or mismatch.');
        }

        $def = dept_product_by_key($productKey);
        $label = (string) ($def['label'] ?? $productKey);
        if ($stockSource === 'rts') {
            $rtsLabel = rts_other_label_for_dept_key($productKey);
            if ($rtsLabel === null) {
                throw new RuntimeException($label . ' cannot be sold from RTS stock.');
            }
            $onHand = rts_inventory_available_other($pdo, $rtsLabel, 'sellable');
            if ($qty > $onHand + 0.0001) {
                throw new RuntimeException(
                    'Not enough ' . $label . " RTS stock ({$onHand} available)."
                );
            }
        } else {
            $onHand = $onHandByKey[$productKey] ?? 0.0;
            if ($qty > $onHand + 0.0001) {
                throw new RuntimeException(
                    'Not enough ' . $label . " on hand ({$onHand} available)."
                );
            }
        }

        $listUnit = round((float) $prod['selling_price'], 2);
        $chargedUnit = round($listUnit * $discountFactor, 2);
        $lineSubtotal = round($listUnit * $qty, 2);
        $lineTotal = round($chargedUnit * $qty, 2);
        $cleanLines[] = [
            'product_id' => $productId,
            'product_key' => $productKey,
            'qty' => $qty,
            'list_unit_price' => $listUnit,
            'unit_price' => $chargedUnit,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineTotal,
            'name' => (string) $prod['name'],
            'rts_label' => $stockSource === 'rts' ? rts_other_label_for_dept_key($productKey) : null,
            'pouch_unit_ids' => $unitIds,
        ];
    }

    return $cleanLines;
}

/**
 * @param list<array{product_key: string, product_id?: int, qty: float}> $items
 * @param array{discount_percent?: int|float|string|null, discount_request_id?: int|null, stock_source?: string} $opts
 * @return array<string, mixed>
 */
function dept_pos_checkout(PDO $pdo, int $posPinId, array $items, string $buyerName, string $buyerCreds, array $opts = []): array
{
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
    }

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

    $cleanLines = dept_pos_build_cart_lines($pdo, $items, $discountFactor, $stockSource);
    $subtotal = round(array_sum(array_column($cleanLines, 'line_subtotal')), 2);
    $total = round(array_sum(array_column($cleanLines, 'line_total')), 2);
    $discountAmount = round($subtotal - $total, 2);

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
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

        $insLine = $pdo->prepare(
            'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($cleanLines as $line) {
            if ($stockSource === 'rts') {
                $rtsLabel = (string) ($line['rts_label'] ?? '');
                if ($rtsLabel === '') {
                    throw new RuntimeException('RTS product label missing for ' . $line['name']);
                }
                rts_inventory_deduct_sell_other_label(
                    $pdo,
                    0,
                    $rtsLabel,
                    (float) $line['qty'],
                    'walk_in',
                    'sellable',
                    [
                        'used_where' => 'pos',
                        'pos_sale_id' => $saleId,
                        'actor_user_id' => null,
                    ]
                );
            } else {
                $unitIds = $line['pouch_unit_ids'] ?? [];
                if (is_array($unitIds) && $unitIds !== []) {
                    dept_pouch_pos_consume($pdo, $unitIds, $saleId);
                } else {
                    dept_inventory_deduct($pdo, $line['product_key'], (float) $line['qty']);
                }
            }
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
            'product_key' => $l['product_key'],
            'qty' => $l['qty'],
            'list_unit_price' => $l['list_unit_price'],
            'unit_price' => $l['unit_price'],
            'line_subtotal' => $l['line_subtotal'],
            'line_total' => $l['line_total'],
            'name' => $l['name'],
        ], $cleanLines),
        'buyer_name' => $buyerName !== '' ? $buyerName : 'Walk-in',
    ];
}

/**
 * @param list<array{product_key: string, product_id?: int, qty: float}> $items
 * @param array{debtor_user_id?: int|null, debtor_name: string, debtor_mobile?: string|null} $debtor
 * @return array<string, mixed>
 */
/**
 * @param list<array{product_key: string, product_id?: int, qty: float}> $items
 * @param array{debtor_user_id?: int|null, debtor_name: string, debtor_mobile?: string|null} $debtor
 * @param array{stock_source?: string} $opts
 * @return array<string, mixed>
 */
function dept_pos_ar_checkout(PDO $pdo, int $posPinId, array $items, array $debtor, array $opts = []): array
{
    if ($posPinId < 1) {
        throw new RuntimeException('POS session is invalid.');
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
    $stockSource = strtolower(trim((string) ($opts['stock_source'] ?? 'packaging')));
    if (!in_array($stockSource, ['packaging', 'rts'], true)) {
        throw new RuntimeException('Invalid stock source.');
    }
    if ($stockSource === 'rts') {
        require_once __DIR__ . '/rts_inventory_sync.php';
    }

    $cleanLines = dept_pos_build_cart_lines($pdo, $items, 1.0, $stockSource);
    $subtotal = round(array_sum(array_column($cleanLines, 'line_subtotal')), 2);
    $total = round(array_sum(array_column($cleanLines, 'line_total')), 2);

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
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

        $insLine = $pdo->prepare(
            'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent)
             VALUES (?, ?, ?, ?, ?, ?, NULL)'
        );

        $arLines = [];
        foreach ($cleanLines as $line) {
            if ($stockSource === 'rts') {
                $rtsLabel = (string) ($line['rts_label'] ?? '');
                if ($rtsLabel === '') {
                    throw new RuntimeException('RTS product label missing for ' . $line['name']);
                }
                rts_inventory_deduct_sell_other_label(
                    $pdo,
                    0,
                    $rtsLabel,
                    (float) $line['qty'],
                    'walk_in',
                    'sellable',
                    [
                        'used_where' => 'pos',
                        'pos_sale_id' => $saleId,
                        'actor_user_id' => null,
                    ]
                );
            } else {
                $unitIds = $line['pouch_unit_ids'] ?? [];
                if (is_array($unitIds) && $unitIds !== []) {
                    dept_pouch_pos_consume($pdo, $unitIds, $saleId);
                } else {
                    dept_inventory_deduct($pdo, $line['product_key'], (float) $line['qty']);
                }
            }
            $insLine->execute([
                $saleId,
                $line['product_id'],
                $line['qty'],
                $line['unit_price'],
                $line['list_unit_price'],
                $line['line_total'],
            ]);
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
            'product_key' => $l['product_key'],
            'qty' => $l['qty'],
            'unit_price' => $l['unit_price'],
            'line_total' => $l['line_total'],
            'name' => $l['name'],
        ], $cleanLines),
        'debtor_name' => $debtorName,
        'debtor_mobile' => $debtorMobile !== '' ? $debtorMobile : null,
    ];
}
