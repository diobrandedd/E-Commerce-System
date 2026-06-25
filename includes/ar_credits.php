<?php

declare(strict_types=1);

/** @return list<string> */
function ar_credits_staff_roles(): array
{
    return [
        'executive',
        'prod_manager',
        'tl',
        'team_leader',
        'staff',
        'csr_tl',
        'csr_staff',
        'hr',
    ];
}

/** @return list<array<string, mixed>> */
function ar_staff_picker_list(PDO $pdo): array
{
    $roles = ['executive', 'prod_manager', 'tl', 'staff', 'hr'];
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $st = $pdo->prepare(
        "SELECT u.id, u.name, d.name AS dept_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.dept_id
         WHERE u.is_active = 1 AND r.name IN ({$placeholders})
         ORDER BY u.name ASC"
    );
    $st->execute($roles);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param array<string, mixed> $user IMS session user
 * @return array{sql: string, params: list<mixed>}
 */
function ar_credits_list_scope(array $user): array
{
    $role = (string) ($user['role'] ?? '');
    if ($role === 'executive') {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => ' WHERE (ar.debtor_user_id = ? OR (ar.debtor_user_id IS NULL AND ar.user_id = ?))',
        'params' => [(int) $user['id'], (int) $user['id']],
    ];
}

/** @return list<array<string, mixed>> */
function ar_credits_list(PDO $pdo, array $user): array
{
    $scope = ar_credits_list_scope($user);
    $sql = 'SELECT ar.id, ar.user_id, ar.debtor_user_id, ar.debtor_name, ar.debtor_mobile,
                   ar.product_id, ar.qty, ar.price_at_time, ar.total_amount,
                   ar.status, ar.taken_at, ar.paid_at, ar.marked_paid_by,
                   ar.pos_sale_id, ar.source, ar.recorded_by_pin_id,
                   COALESCE(debtor.name, u.name) AS user_name,
                   COALESCE(debtor_dept.name, d.name) AS dept_name,
                   p.name AS product_name, p.size AS product_size, p.variant AS product_variant,
                   pm.name AS paid_by_name,
                   ps.receipt_no
            FROM ar_credits ar
            LEFT JOIN users u ON u.id = ar.user_id
            LEFT JOIN users debtor ON debtor.id = ar.debtor_user_id
            LEFT JOIN departments d ON d.id = u.dept_id
            LEFT JOIN departments debtor_dept ON debtor_dept.id = debtor.dept_id
            INNER JOIN products p ON p.id = ar.product_id
            LEFT JOIN users pm ON pm.id = ar.marked_paid_by
            LEFT JOIN pos_sales ps ON ps.id = ar.pos_sale_id'
        . $scope['sql']
        . ' ORDER BY ar.taken_at DESC, ar.id DESC LIMIT 500';
    $st = $pdo->prepare($sql);
    $st->execute($scope['params']);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param array{debtor_user_id?: int|null, debtor_name: string, debtor_mobile?: string|null, source?: string, recorded_by_pin_id?: int|null, pos_sale_id?: int|null} $debtor
 * @param list<array{product_id: int, qty: float, unit_price: float}> $lines
 * @return list<int> ar_credit ids
 */
function ar_credits_record_lines(PDO $pdo, array $debtor, array $lines): array
{
    if ($lines === []) {
        throw new RuntimeException('No AR lines to record.');
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
    $source = (string) ($debtor['source'] ?? 'pos');
    if (!in_array($source, ['ims_take', 'pos'], true)) {
        $source = 'pos';
    }
    $recordedByPinId = isset($debtor['recorded_by_pin_id']) ? (int) $debtor['recorded_by_pin_id'] : null;
    if ($recordedByPinId !== null && $recordedByPinId < 1) {
        $recordedByPinId = null;
    }
    $posSaleId = isset($debtor['pos_sale_id']) ? (int) $debtor['pos_sale_id'] : null;
    if ($posSaleId !== null && $posSaleId < 1) {
        $posSaleId = null;
    }

    $userId = $debtorUserId;
    $ins = $pdo->prepare(
        'INSERT INTO ar_credits (
            user_id, debtor_user_id, debtor_name, debtor_mobile, product_id,
            qty, price_at_time, total_amount, status, taken_at,
            pos_sale_id, source, recorded_by_pin_id
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "outstanding", NOW(), ?, ?, ?)'
    );

    $ids = [];
    foreach ($lines as $line) {
        $productId = (int) ($line['product_id'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        $price = round((float) ($line['unit_price'] ?? 0), 2);
        if ($productId < 1 || $qty <= 0 || $price < 0) {
            throw new RuntimeException('Invalid AR line.');
        }
        $total = round($price * $qty, 2);
        $ins->execute([
            $userId,
            $debtorUserId,
            $debtorName,
            $debtorMobile === '' ? null : $debtorMobile,
            $productId,
            $qty,
            $price,
            $total,
            $posSaleId,
            $source,
            $recordedByPinId,
        ]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    return $ids;
}

function ar_credits_mark_paid(PDO $pdo, int $creditId, int $markedByUserId): void
{
    $upd = $pdo->prepare(
        'UPDATE ar_credits
         SET status = "paid", paid_at = NOW(), marked_paid_by = ?
         WHERE id = ? AND status = "outstanding"'
    );
    $upd->execute([$markedByUserId, $creditId]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Credit not found or already paid.');
    }
}

function ar_credits_mark_unpaid(PDO $pdo, int $creditId): void
{
    $upd = $pdo->prepare(
        'UPDATE ar_credits
         SET status = "outstanding", paid_at = NULL, marked_paid_by = NULL
         WHERE id = ? AND status = "paid"'
    );
    $upd->execute([$creditId]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Credit not found or not paid.');
    }
}

/** IMS take-on-credit (legacy page). */
function ar_credits_take_for_user(PDO $pdo, int $userId, int $productId, float $qty): array
{
    if ($productId < 1 || $qty <= 0) {
        throw new RuntimeException('Invalid product or quantity.');
    }
    $st = $pdo->prepare('SELECT id, name, selling_price FROM products WHERE id = ? LIMIT 1');
    $st->execute([$productId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Product not found.');
    }
    $stUser = $pdo->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stUser->execute([$userId]);
    $userRow = $stUser->fetch(PDO::FETCH_ASSOC);
    if (!$userRow) {
        throw new RuntimeException('User not found.');
    }

    $price = round((float) $row['selling_price'], 2);
    $total = round($price * $qty, 2);
    $ids = ar_credits_record_lines($pdo, [
        'debtor_user_id' => $userId,
        'debtor_name' => (string) $userRow['name'],
        'source' => 'ims_take',
    ], [['product_id' => $productId, 'qty' => $qty, 'unit_price' => $price]]);

    return ['id' => $ids[0], 'total' => $total];
}

/** @return list<array<string, mixed>> */
function ar_credits_sellable_products(PDO $pdo): array
{
    $st = $pdo->query(
        "SELECT p.id, p.name, p.size, p.variant, p.selling_price, d.name AS dept_name
         FROM products p
         INNER JOIN departments d ON d.id = p.dept_id
         WHERE p.selling_price > 0
         ORDER BY d.name ASC, p.name ASC"
    );

    return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}
