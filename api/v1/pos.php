<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * Walk-in POS terminal API.  Requires the dedicated POS session created by
 * `ims_login_pos()` — POS users cannot access any non-POS endpoint.
 *
 *   GET  ?action=products        -> sellable products with selling_price
 *   POST ?action=checkout        -> {items, buyer_name?, buyer_credentials?}
 */

api_dispatch([
    'products' => 'api_pos_products',
    'checkout' => 'api_pos_checkout',
]);

function api_pos_require_session(): void
{
    if (!ims_pos_authenticated()) {
        api_fail('POS session required.', 401);
    }
}

function api_pos_products(): void
{
    api_method_allowed(['GET']);
    api_pos_require_session();
    $pdo = ims_pdo();
    $st = $pdo->query(
        "SELECT p.id, p.name, p.size, p.variant, p.selling_price, p.baseline_cost,
                d.name AS dept_name
         FROM products p
         INNER JOIN departments d ON d.id = p.dept_id
         WHERE p.selling_price > 0
         ORDER BY d.name ASC, p.name ASC"
    );
    api_ok(['products' => $st ? $st->fetchAll() : []]);
}

function api_pos_checkout(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_pos_require_session();

    $items = is_array($input['items'] ?? null) ? $input['items'] : [];
    $buyerName = trim((string) ($input['buyer_name'] ?? 'Walk-in'));
    $buyerCreds = trim((string) ($input['buyer_credentials'] ?? ''));
    $pinId = (int) ($_SESSION['pos_pin_id'] ?? 0);

    if ($pinId < 1) {
        api_fail('POS session is invalid.', 401);
    }
    if (!$items) {
        api_fail('Cart is empty.', 422);
    }

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $total = 0.0;
        $cleanItems = [];
        foreach ($items as $entry) {
            $productId = (int) ($entry['product_id'] ?? 0);
            $qty = (float) ($entry['qty'] ?? 0);
            if ($productId < 1) {
                throw new RuntimeException('Invalid product in cart.');
            }
            if ($qty <= 0) {
                throw new RuntimeException('Quantities must be > 0.');
            }
            $st = $pdo->prepare('SELECT id, name, selling_price FROM products WHERE id = ? LIMIT 1');
            $st->execute([$productId]);
            $row = $st->fetch();
            if (!$row) {
                throw new RuntimeException('Product not found.');
            }
            $unit = round((float) $row['selling_price'], 2);
            $lineTotal = round($unit * $qty, 2);
            $total += $lineTotal;
            $cleanItems[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
                'name' => (string) $row['name'],
            ];
        }
        $total = round($total, 2);

        $receiptNo = sprintf('RCP-%s', strtoupper(bin2hex(random_bytes(4))));
        $insSale = $pdo->prepare(
            'INSERT INTO pos_sales (receipt_no, buyer_name, buyer_credentials, total_amount, sold_by_pin_id, sale_date)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $insSale->execute([
            $receiptNo,
            $buyerName !== '' ? $buyerName : 'Walk-in',
            $buyerCreds === '' ? null : $buyerCreds,
            $total,
            $pinId,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $insLine = $pdo->prepare(
            'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, line_total)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($cleanItems as $item) {
            $insLine->execute([
                $saleId,
                $item['product_id'],
                $item['qty'],
                $item['unit_price'],
                $item['line_total'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    api_ok([
        'sale_id' => $saleId,
        'receipt_no' => $receiptNo,
        'total' => $total,
        'items' => $cleanItems,
        'buyer_name' => $buyerName !== '' ? $buyerName : 'Walk-in',
    ]);
}
