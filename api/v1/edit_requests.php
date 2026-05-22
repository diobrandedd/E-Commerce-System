<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * Edit request workflow.
 *
 *   GET  ?action=list           -> Executive sees pending; others see own
 *   POST ?action=submit         -> Staff/TL submit a new edit request
 *   POST ?action=approve        -> Executive approves and applies change
 *   POST ?action=reject         -> Executive rejects with rejection_note
 */

api_dispatch([
    'list' => 'api_er_list',
    'submit' => 'api_er_submit',
    'approve' => 'api_er_approve',
    'reject' => 'api_er_reject',
]);

const ER_ALLOWED_TABLES = ['stock_entries', 'stocks', 'products', 'beginning_inventory'];

function api_er_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $pdo = ims_pdo();

    $sql = 'SELECT er.id, er.table_name, er.record_id, er.field_name, er.old_value, er.new_value,
                   er.reason, er.status, er.rejection_note, er.created_at, er.reviewed_at,
                   u.name AS requested_by_name, d.name AS dept_name,
                   r.name AS reviewer_name
            FROM edit_requests er
            INNER JOIN users u ON u.id = er.requested_by
            LEFT JOIN departments d ON d.id = u.dept_id
            LEFT JOIN users r ON r.id = er.reviewed_by';
    $params = [];
    if ($role === 'executive') {
        $statusFilter = trim((string) ($_GET['status'] ?? 'pending'));
        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $sql .= ' WHERE er.status = ?';
            $params[] = $statusFilter;
        }
    } else {
        $sql .= ' WHERE er.requested_by = ?';
        $params[] = (int) $user['id'];
    }
    $sql .= ' ORDER BY er.created_at DESC, er.id DESC LIMIT 200';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    api_ok([
        'requests' => $st->fetchAll(),
        'is_reviewer' => $role === 'executive',
    ]);
}

function api_er_submit(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['tl', 'staff']);

    $table = trim((string) ($input['table_name'] ?? ''));
    $recordId = (int) ($input['record_id'] ?? 0);
    $field = trim((string) ($input['field_name'] ?? ''));
    $oldValue = (string) ($input['old_value'] ?? '');
    $newValue = (string) ($input['new_value'] ?? '');
    $reason = trim((string) ($input['reason'] ?? ''));

    if (!in_array($table, ER_ALLOWED_TABLES, true)) {
        api_fail('Invalid table.', 422);
    }
    if ($recordId < 1) {
        api_fail('record_id is required.', 422);
    }
    if ($field === '') {
        api_fail('field_name is required.', 422);
    }
    if ($reason === '') {
        api_fail('Reason is required.', 422);
    }

    $pdo = ims_pdo();
    // Verify record exists and is in scope (same dept) for the requester.
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    if ($table === 'stock_entries') {
        $st = $pdo->prepare('SELECT dept_id FROM stock_entries WHERE id = ? LIMIT 1');
        $st->execute([$recordId]);
        $row = $st->fetch();
        if (!$row || (int) $row['dept_id'] !== $userDeptId) {
            api_fail('Record not in your department.', 403);
        }
    }

    $ins = $pdo->prepare(
        'INSERT INTO edit_requests (table_name, record_id, field_name, old_value, new_value, reason, requested_by, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, "pending")'
    );
    $ins->execute([$table, $recordId, $field, $oldValue, $newValue, $reason, (int) $user['id']]);
    api_ok(['id' => (int) $pdo->lastInsertId()]);
}

function api_er_approve(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid request id.', 422);
    }
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM edit_requests WHERE id = ? AND status = "pending" LIMIT 1 FOR UPDATE');
        $st->execute([$id]);
        $req = $st->fetch();
        if (!$req) {
            throw new RuntimeException('Request not found or already reviewed.');
        }
        $table = (string) $req['table_name'];
        $recordId = (int) $req['record_id'];
        $field = (string) $req['field_name'];
        $newValue = (string) ($req['new_value'] ?? '');

        if (!in_array($table, ER_ALLOWED_TABLES, true)) {
            throw new RuntimeException('Table not allowed.');
        }
        // Whitelist updatable fields per table for safety.
        $allowedFields = [
            'stock_entries' => ['qty', 'batching_date', 'supplier_batch_no'],
            'stocks' => ['name', 'unit', 'supplier_name', 'supplier_code'],
            'products' => ['name', 'size', 'variant', 'selling_price'],
            'beginning_inventory' => ['qty', 'month_year'],
        ];
        if (!isset($allowedFields[$table]) || !in_array($field, $allowedFields[$table], true)) {
            throw new RuntimeException('Field not allowed.');
        }
        $sql = 'UPDATE ' . $table . ' SET ' . $field . ' = ? WHERE id = ?';
        $upd = $pdo->prepare($sql);
        $upd->execute([$newValue, $recordId]);

        $review = $pdo->prepare(
            'UPDATE edit_requests SET status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        );
        $review->execute([(int) $user['id'], $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['approved' => true]);
}

function api_er_reject(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    $note = trim((string) ($input['rejection_note'] ?? ''));
    if ($id < 1) {
        api_fail('Invalid request id.', 422);
    }
    if ($note === '') {
        api_fail('Rejection note is required.', 422);
    }
    $pdo = ims_pdo();
    $upd = $pdo->prepare(
        'UPDATE edit_requests
         SET status = "rejected", reviewed_by = ?, reviewed_at = NOW(), rejection_note = ?
         WHERE id = ? AND status = "pending"'
    );
    $upd->execute([(int) $user['id'], $note, $id]);
    if ($upd->rowCount() < 1) {
        api_fail('Request not found or already reviewed.', 404);
    }
    api_ok(['rejected' => true]);
}
