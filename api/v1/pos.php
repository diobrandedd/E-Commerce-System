<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/honey_pos.php';
require_once __DIR__ . '/../../includes/dept_pos.php';
require_once __DIR__ . '/../../includes/pos_discount_requests.php';

/**
 * Walk-in POS terminal API.  Requires the dedicated POS session created by
 * `ims_login_pos()` — POS users cannot access any non-POS endpoint.
 *
 *   GET  ?action=products         -> sellable products with selling_price (legacy)
 *   GET  ?action=honey_catalog    -> honey variants, sizes, on-hand, prices
 *   POST ?action=checkout         -> legacy flat product checkout
 *   POST ?action=honey_checkout   -> honey cart with packaging deduct + sales log
 */

api_dispatch([
    'products' => 'api_pos_products',
    'honey_catalog' => 'api_pos_honey_catalog',
    'dept_catalog' => 'api_pos_dept_catalog',
    'checkout' => 'api_pos_checkout',
    'honey_checkout' => 'api_pos_honey_checkout',
    'dept_checkout' => 'api_pos_dept_checkout',
    'discount_request_create' => 'api_pos_discount_request_create',
    'discount_request_status' => 'api_pos_discount_request_status',
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

function api_pos_honey_catalog(): void
{
    api_method_allowed(['GET']);
    api_pos_require_session();
    try {
        api_ok(honey_pos_honey_catalog(ims_pdo()));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_pos_dept_catalog(): void
{
    api_method_allowed(['GET']);
    api_pos_require_session();
    $deptKey = trim((string) ($_GET['dept'] ?? ''));
    try {
        api_ok(dept_pos_catalog(ims_pdo(), $deptKey !== '' ? $deptKey : null));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_pos_dept_checkout(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_pos_require_session();

    $pinId = (int) ($_SESSION['pos_pin_id'] ?? 0);
    $items = is_array($input['items'] ?? null) ? $input['items'] : [];
    $buyerName = trim((string) ($input['buyer_name'] ?? 'Walk-in'));
    $buyerCreds = trim((string) ($input['buyer_credentials'] ?? ''));

    try {
        $result = dept_pos_checkout(ims_pdo(), $pinId, $items, $buyerName, $buyerCreds);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($result);
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
            'INSERT INTO pos_sales (
                receipt_no, buyer_name, buyer_credentials, subtotal_amount, discount_percent, discount_amount, total_amount, sold_by_pin_id, sale_date
             ) VALUES (?, ?, ?, ?, NULL, 0, ?, ?, NOW())'
        );
        $insSale->execute([
            $receiptNo,
            $buyerName !== '' ? $buyerName : 'Walk-in',
            $buyerCreds === '' ? null : $buyerCreds,
            $total,
            $total,
            $pinId,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $insLine = $pdo->prepare(
            'INSERT INTO pos_receipt_items (sale_id, product_id, qty, unit_price, list_unit_price, line_total, discount_percent)
             VALUES (?, ?, ?, ?, ?, ?, NULL)'
        );
        foreach ($cleanItems as $item) {
            $insLine->execute([
                $saleId,
                $item['product_id'],
                $item['qty'],
                $item['unit_price'],
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

function api_pos_honey_checkout(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_pos_require_session();

    $pinId = (int) ($_SESSION['pos_pin_id'] ?? 0);
    $items = is_array($input['items'] ?? null) ? $input['items'] : [];
    $buyerName = trim((string) ($input['buyer_name'] ?? 'Walk-in'));
    $buyerCreds = trim((string) ($input['buyer_credentials'] ?? ''));

    try {
        $result = honey_pos_checkout(ims_pdo(), $pinId, $items, $buyerName, $buyerCreds);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($result);
}

function api_pos_discount_request_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_pos_require_session();

    $pinId = (int) ($_SESSION['pos_pin_id'] ?? 0);
    $items = is_array($input['items'] ?? null) ? $input['items'] : [];
    $buyerName = trim((string) ($input['buyer_name'] ?? ''));
    $buyerCreds = trim((string) ($input['buyer_credentials'] ?? ''));
    $discountPercent = (int) ($input['discount_percent'] ?? 0);

    try {
        $result = pos_discount_request_create(
            ims_pdo(),
            $pinId,
            $items,
            $buyerName,
            $buyerCreds,
            $discountPercent
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($result);
}

function api_pos_discount_request_status(): void
{
    api_method_allowed(['GET']);
    api_pos_require_session();

    $pinId = (int) ($_SESSION['pos_pin_id'] ?? 0);
    $requestId = (int) ($_GET['request_id'] ?? 0);
    if ($requestId < 1) {
        api_fail('request_id is required.', 422);
    }

    try {
        $result = pos_discount_request_fetch_for_pin(ims_pdo(), $requestId, $pinId);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($result);
}
