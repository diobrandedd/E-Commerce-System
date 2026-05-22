<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * Accounts-Receivable (employee product credits) API.
 *
 *   GET  ?action=list          -> own credits for staff/TL/PM; all for executive
 *   POST ?action=take          -> staff/TL/PM takes a product on credit
 *   POST ?action=mark_paid     -> executive marks a credit as paid
 */

api_dispatch([
    'list' => 'api_ar_list',
    'take' => 'api_ar_take',
    'mark_paid' => 'api_ar_mark_paid',
    'products' => 'api_ar_products',
]);

function api_ar_products(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $pdo = ims_pdo();
    $st = $pdo->query(
        "SELECT p.id, p.name, p.size, p.variant, p.selling_price, d.name AS dept_name
         FROM products p
         INNER JOIN departments d ON d.id = p.dept_id
         WHERE p.selling_price > 0
         ORDER BY d.name ASC, p.name ASC"
    );
    api_ok(['products' => $st ? $st->fetchAll() : []]);
}

function api_ar_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $pdo = ims_pdo();

    $sql = 'SELECT ar.id, ar.user_id, ar.product_id, ar.qty, ar.price_at_time, ar.total_amount,
                   ar.status, ar.taken_at, ar.paid_at, ar.marked_paid_by,
                   u.name AS user_name, d.name AS dept_name,
                   p.name AS product_name, p.size AS product_size, p.variant AS product_variant,
                   pm.name AS paid_by_name
            FROM ar_credits ar
            INNER JOIN users u ON u.id = ar.user_id
            LEFT JOIN departments d ON d.id = u.dept_id
            INNER JOIN products p ON p.id = ar.product_id
            LEFT JOIN users pm ON pm.id = ar.marked_paid_by';
    $params = [];
    if ($role !== 'executive') {
        $sql .= ' WHERE ar.user_id = ?';
        $params[] = (int) $user['id'];
    }
    $sql .= ' ORDER BY ar.taken_at DESC, ar.id DESC LIMIT 500';
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
    } catch (Throwable) {
        // Table likely missing — return empty with a hint.
        api_ok(['credits' => [], 'missing_migration' => true]);
    }
    api_ok(['credits' => $rows]);
}

function api_ar_take(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!in_array($role, ['executive', 'prod_manager', 'tl', 'staff'], true)) {
        api_fail('Forbidden.', 403);
    }

    $productId = (int) ($input['product_id'] ?? 0);
    $qty = (float) ($input['qty'] ?? 0);
    if ($productId < 1 || $qty <= 0) {
        api_fail('Invalid product or quantity.', 422);
    }
    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT id, selling_price FROM products WHERE id = ? LIMIT 1');
    $st->execute([$productId]);
    $row = $st->fetch();
    if (!$row) {
        api_fail('Product not found.', 404);
    }
    $price = round((float) $row['selling_price'], 2);
    $total = round($price * $qty, 2);

    $ins = $pdo->prepare(
        'INSERT INTO ar_credits (user_id, product_id, qty, price_at_time, total_amount, status, taken_at)
         VALUES (?, ?, ?, ?, ?, "outstanding", NOW())'
    );
    $ins->execute([(int) $user['id'], $productId, $qty, $price, $total]);
    api_ok(['id' => (int) $pdo->lastInsertId(), 'total' => $total]);
}

function api_ar_mark_paid(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid id.', 422);
    }
    $pdo = ims_pdo();
    $upd = $pdo->prepare(
        'UPDATE ar_credits
         SET status = "paid", paid_at = NOW(), marked_paid_by = ?
         WHERE id = ? AND status = "outstanding"'
    );
    $upd->execute([(int) $user['id'], $id]);
    if ($upd->rowCount() < 1) {
        api_fail('Credit not found or already paid.', 404);
    }
    api_ok(['paid' => true]);
}
