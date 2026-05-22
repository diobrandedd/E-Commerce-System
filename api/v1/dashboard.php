<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * Dashboard data for the SPA.  Replaces queries from
 * `exec/dashboard.php` and the equivalent staff/TL home pages.
 */

api_dispatch([
    'index' => 'api_dashboard_index',
    'summary' => 'api_dashboard_index',
]);

function api_dashboard_index(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $pdo = ims_pdo();

    $role = (string) $user['role'];
    $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $isExec = $role === 'executive';
    $isProdMgr = $role === 'prod_manager';
    $isCrossDept = $isExec || $isProdMgr;

    $payload = [
        'role' => $role,
        'name' => (string) $user['name'],
        'dept_id' => $deptId > 0 ? $deptId : null,
        'stats' => [],
        'pending_edit_requests' => [],
        'pending_edit_requests_count' => 0,
        'recent_stock_changes' => [],
        'recent_cost_changes' => [],
    ];

    // ---- Stats -----------------------------------------------------------------

    $stats = [];
    $stocksWhere = $isCrossDept ? '' : ' WHERE dept_id = :dept';
    $stocksParams = $isCrossDept ? [] : ['dept' => $deptId];
    $st = $pdo->prepare("SELECT COUNT(*) FROM stocks{$stocksWhere}");
    $st->execute($stocksParams);
    $stats['stocks'] = (int) $st->fetchColumn();

    $productsWhere = $isCrossDept ? '' : ' WHERE dept_id = :dept';
    $st = $pdo->prepare("SELECT COUNT(*) FROM products{$productsWhere}");
    $st->execute($stocksParams);
    $stats['products'] = (int) $st->fetchColumn();

    if ($isCrossDept) {
        $st = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
        $stats['active_users'] = $st ? (int) $st->fetchColumn() : 0;
    } else {
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE is_active = 1 AND dept_id = ?');
        $st->execute([$deptId]);
        $stats['active_users'] = (int) $st->fetchColumn();
    }

    $sinceMonth = (new DateTimeImmutable('-30 days'))->format('Y-m-d 00:00:00');
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM stock_entries WHERE created_at >= ?'
        . ($isCrossDept ? '' : ' AND dept_id = ?')
    );
    $params = $isCrossDept ? [$sinceMonth] : [$sinceMonth, $deptId];
    $st->execute($params);
    $stats['stock_entries_30d'] = (int) $st->fetchColumn();

    $payload['stats'] = $stats;

    // ---- Edit requests ---------------------------------------------------------

    if ($isExec) {
        $st = $pdo->query("SELECT COUNT(*) FROM edit_requests WHERE status = 'pending'");
        $payload['pending_edit_requests_count'] = $st ? (int) $st->fetchColumn() : 0;

        $st = $pdo->query(
            "SELECT er.id, er.table_name, er.record_id, er.field_name, er.old_value, er.new_value,
                    er.reason, er.created_at, u.name AS requested_by_name, d.name AS dept_name
             FROM edit_requests er
             INNER JOIN users u ON u.id = er.requested_by
             LEFT JOIN departments d ON d.id = u.dept_id
             WHERE er.status = 'pending'
             ORDER BY er.created_at DESC
             LIMIT 8"
        );
        $payload['pending_edit_requests'] = $st ? $st->fetchAll() : [];
    } else {
        $st = $pdo->prepare(
            "SELECT id, table_name, record_id, field_name, old_value, new_value, reason, status,
                    rejection_note, created_at, reviewed_at
             FROM edit_requests
             WHERE requested_by = ?
             ORDER BY created_at DESC
             LIMIT 6"
        );
        $st->execute([(int) $user['id']]);
        $payload['pending_edit_requests'] = $st->fetchAll();
        $st = $pdo->prepare("SELECT COUNT(*) FROM edit_requests WHERE requested_by = ? AND status = 'pending'");
        $st->execute([(int) $user['id']]);
        $payload['pending_edit_requests_count'] = (int) $st->fetchColumn();
    }

    // ---- Recent stock price changes -------------------------------------------

    try {
        $st = $pdo->query(
            "SELECT sph.id, sph.old_price, sph.new_price, sph.changed_at,
                    s.name AS stock_name, d.name AS dept_name, u.name AS changed_by_name
             FROM stock_price_history sph
             INNER JOIN stocks s ON s.id = sph.stock_id
             LEFT JOIN departments d ON d.id = s.dept_id
             LEFT JOIN users u ON u.id = sph.changed_by
             ORDER BY sph.changed_at DESC
             LIMIT 6"
        );
        $payload['recent_stock_changes'] = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        $payload['recent_stock_changes'] = [];
    }

    // ---- Recent cost recalculations -------------------------------------------

    try {
        $st = $pdo->query(
            "SELECT pch.id, pch.old_cost, pch.new_cost, pch.recalculated_at,
                    p.name AS product_name, d.name AS dept_name
             FROM product_cost_history pch
             INNER JOIN products p ON p.id = pch.product_id
             LEFT JOIN departments d ON d.id = p.dept_id
             ORDER BY pch.recalculated_at DESC
             LIMIT 6"
        );
        $payload['recent_cost_changes'] = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        $payload['recent_cost_changes'] = [];
    }

    api_ok($payload);
}
