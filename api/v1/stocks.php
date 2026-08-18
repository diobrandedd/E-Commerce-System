<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_costing_helpers.php';

/**
 * Stock entry + listing API (Phase 2/3 reuse).
 *
 *   GET  ?action=entries_list&dept_id=...  -> rows visible to current user
 *   GET  ?action=stocks_for_dept&dept_id=  -> stocks scoped to that dept (for dropdowns)
 *   GET  ?action=departments               -> all departments
 *   POST ?action=add_entry                 -> add stock entry (creates stock if needed). Executive only by .cursorrules.
 */

api_dispatch([
    'entries_list' => 'api_stocks_entries_list',
    'stocks_for_dept' => 'api_stocks_for_dept',
    'departments' => 'api_stocks_departments',
    'add_entry' => 'api_stocks_add_entry',
]);

function api_stocks_can_view_all(array $user): bool
{
    return ims_user_can($user, 'reports.view');
}

function api_stocks_can_add(array $user): bool
{
    return ims_user_can($user, 'inventory.entries.add');
}

function api_stocks_entries_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $userId = (int) $user['id'];
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_stocks_can_view_all($user);
    $requestedDept = isset($_GET['dept_id']) ? (int) $_GET['dept_id'] : 0;

    if (!$canViewAll && $userDeptId < 1) {
        api_fail('Your account has no department assigned.', 400);
    }

    $pdo = ims_pdo();
    $sql = 'SELECT se.id, se.stock_id, se.dept_id, se.qty, se.batching_date, se.supplier_batch_no,
                   se.entered_by, se.created_at,
                   s.name AS stock_name, s.unit, s.current_price, s.supplier_name, s.supplier_code,
                   d.name AS dept_name, u.name AS entered_by_name
            FROM stock_entries se
            INNER JOIN stocks s ON s.id = se.stock_id
            INNER JOIN departments d ON d.id = se.dept_id
            INNER JOIN users u ON u.id = se.entered_by';
    $where = [];
    $params = [];
    if ($role === 'staff') {
        $where[] = 'se.dept_id = ?';
        $where[] = 'se.entered_by = ?';
        $params[] = $userDeptId;
        $params[] = $userId;
    } elseif ($role === 'tl') {
        $where[] = 'se.dept_id = ?';
        $params[] = $userDeptId;
    } elseif (!$canViewAll) {
        $where[] = 'se.dept_id = ?';
        $params[] = $userDeptId;
    } elseif ($requestedDept > 0) {
        $where[] = 'se.dept_id = ?';
        $params[] = $requestedDept;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY se.created_at DESC, se.id DESC LIMIT 500';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    api_ok([
        'entries' => $rows,
        'can_view_supplier' => ims_user_can($user, 'suppliers.view'),
        'can_view_pricing' => ims_user_can($user, 'honey.pricing.view_cost'),
        'can_add' => api_stocks_can_add($user),
        'can_view_all' => $canViewAll,
    ]);
}

function api_stocks_for_dept(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_stocks_can_view_all($user);
    $deptId = isset($_GET['dept_id']) ? (int) $_GET['dept_id'] : ($canViewAll ? 0 : $userDeptId);
    if (!$canViewAll && $deptId !== $userDeptId) {
        api_fail('Out of scope.', 403);
    }
    if ($deptId < 1) {
        api_fail('dept_id required.', 422);
    }
    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT id, name, unit, current_price, supplier_name, supplier_code FROM stocks WHERE dept_id = ? ORDER BY name ASC');
    $st->execute([$deptId]);
    api_ok(['stocks' => $st->fetchAll()]);
}

function api_stocks_departments(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $pdo = ims_pdo();
    $st = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    api_ok(['departments' => $st ? $st->fetchAll() : []]);
}

function api_stocks_add_entry(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('inventory.entries.add');
    $userId = (int) $user['id'];
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_stocks_can_view_all($user);

    $stockName = trim((string) ($input['stock_name'] ?? ''));
    $unit = trim((string) ($input['unit'] ?? 'pcs'));
    $qty = (float) ($input['qty'] ?? 0);
    $batching = trim((string) ($input['batching_date'] ?? ''));
    $supplierBatchNo = trim((string) ($input['supplier_batch_no'] ?? ''));
    $supplierName = trim((string) ($input['supplier_name'] ?? ''));
    $supplierCode = trim((string) ($input['supplier_code'] ?? ''));
    $priceRaw = trim((string) ($input['current_price'] ?? ''));
    $deptId = $canViewAll ? (int) ($input['dept_id'] ?? 0) : $userDeptId;

    if ($deptId < 1) {
        api_fail('Department is required.', 422);
    }
    if ($stockName === '') {
        api_fail('Stock name is required.', 422);
    }
    if ($qty <= 0) {
        api_fail('Quantity must be > 0.', 422);
    }
    if ($batching === '') {
        api_fail('Batching date is required.', 422);
    }
    $batching = str_replace('T', ' ', $batching);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $batching)) {
        api_fail('Invalid batching date format.', 422);
    }
    if (!str_contains($batching, ':')) {
        $batching .= ' 00:00:00';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $batching)) {
        $batching .= ':00';
    }
    $price = 0.0;
    if ($priceRaw !== '') {
        if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
            api_fail('Price cannot be negative.', 422);
        }
        $price = round((float) $priceRaw, 2);
    }

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $stLock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE dept_id = ? AND name = ? LIMIT 1 FOR UPDATE');
        $stLock->execute([$deptId, $stockName]);
        $row = $stLock->fetch();
        $stockId = (int) ($row['id'] ?? 0);
        if ($stockId > 0) {
            $oldPrice = round((float) ($row['current_price'] ?? 0), 2);
            $upd = $pdo->prepare('UPDATE stocks SET unit = ?, supplier_name = ?, supplier_code = ? WHERE id = ?');
            $upd->execute([
                $unit,
                $supplierName === '' ? null : $supplierName,
                $supplierCode === '' ? null : $supplierCode,
                $stockId,
            ]);
            if ($priceRaw !== '' && $price !== $oldPrice) {
                api_costing_apply_price_change($pdo, $stockId, $oldPrice, $price, $userId);
            }
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO stocks (name, dept_id, unit, current_price, supplier_name, supplier_code, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $stockName,
                $deptId,
                $unit,
                $price,
                $supplierName === '' ? null : $supplierName,
                $supplierCode === '' ? null : $supplierCode,
                $userId,
            ]);
            $stockId = (int) $pdo->lastInsertId();
        }

        $insEntry = $pdo->prepare(
            'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insEntry->execute([
            $stockId,
            $deptId,
            $qty,
            $batching,
            $supplierBatchNo === '' ? null : $supplierBatchNo,
            $userId,
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['stock_id' => $stockId]);
}
