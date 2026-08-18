<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/dashboard_inventory.php';

/**
 * Dashboard data for the SPA.  Replaces queries from
 * `exec/dashboard.php` and the equivalent staff/TL home pages.
 */

api_dispatch([
    'index' => 'api_dashboard_index',
    'summary' => 'api_dashboard_index',
    'inventory_snapshot' => 'api_dashboard_inventory_snapshot',
    'shipped_summary' => 'api_dashboard_shipped_summary',
]);

function api_dashboard_view_for_user(array $user): string
{
    if (ims_user_can($user, 'csr.parcels.view') || ims_user_can($user, 'csr.online_orders')) {
        return 'csr';
    }
    if ((string) $user['role'] === 'staff' && !ims_user_can($user, 'inventory.entries.view')) {
        return 'staff';
    }

    return 'inventory';
}

function api_dashboard_csr_metrics(PDO $pdo): array
{
    $metrics = [
        'parcels_by_status' => [
            'packing' => 0,
            'shipped' => 0,
            'on_hold' => 0,
            'cancelled' => 0,
            'returned' => 0,
        ],
        'parcels_shipped_today' => 0,
        'parcels_packing' => 0,
        'rts_pool_count' => 0,
        'recent_parcels' => [],
    ];

    try {
        $st = $pdo->query(
            'SELECT status, COUNT(*) AS cnt FROM online_order_parcels GROUP BY status'
        );
        if ($st) {
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $status = (string) ($row['status'] ?? '');
                if (array_key_exists($status, $metrics['parcels_by_status'])) {
                    $metrics['parcels_by_status'][$status] = (int) $row['cnt'];
                }
            }
        }
        $metrics['parcels_packing'] = $metrics['parcels_by_status']['packing'];
    } catch (Throwable) {
        // Table may be missing on older installs.
    }

    try {
        $st = $pdo->query(
            'SELECT COUNT(*) FROM online_order_parcels
             WHERE status = "shipped" AND ship_date IS NOT NULL AND DATE(ship_date) = CURDATE()'
        );
        $metrics['parcels_shipped_today'] = $st ? (int) $st->fetchColumn() : 0;
    } catch (Throwable) {
        $metrics['parcels_shipped_today'] = 0;
    }

    try {
        $st = $pdo->query(
            'SELECT COALESCE(SUM(li.quantity), 0)
             FROM rts_line_items li
             INNER JOIN rts_cases c ON c.id = li.case_id
             WHERE c.voided_at IS NULL'
        );
        $metrics['rts_pool_count'] = $st ? (int) $st->fetchColumn() : 0;
    } catch (Throwable) {
        $metrics['rts_pool_count'] = 0;
    }

    try {
        $st = $pdo->query(
            'SELECT id, tracking_number, recipient_name, courier, status, ship_date, created_at
             FROM online_order_parcels
             ORDER BY id DESC
             LIMIT 6'
        );
        $metrics['recent_parcels'] = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable) {
        $metrics['recent_parcels'] = [];
    }

    return $metrics;
}

function api_dashboard_index(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $pdo = ims_pdo();

    $role = (string) $user['role'];
    $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canApproveEditRequests = ims_user_can($user, 'inventory.edit_requests.approve');
    $isCrossDept = ims_user_can($user, 'inventory.entries.view');
    $view = api_dashboard_view_for_user($user);

    $payload = [
        'view' => $view,
        'can_view_financials' => ims_user_can($user, 'reports.view'),
        'role' => $role,
        'name' => (string) $user['name'],
        'dept_id' => $deptId > 0 ? $deptId : null,
        'stats' => [],
        'pending_edit_requests' => [],
        'pending_edit_requests_count' => 0,
        'recent_stock_changes' => [],
        'recent_cost_changes' => [],
        'csr' => null,
    ];

    if ($view === 'csr') {
        $payload['csr'] = api_dashboard_csr_metrics($pdo);
        api_ok($payload);

        return;
    }

    if ($view === 'staff') {
        api_dashboard_populate_edit_requests($pdo, $user, $canApproveEditRequests, $payload);
        api_ok($payload);

        return;
    }

    // ---- Inventory view stats (exec, prod_manager, hr, team_leader) ----------

    $stats = [];
    $stocksParams = $isCrossDept ? [] : ['dept' => $deptId];
    $stocksWhere = $isCrossDept ? '' : ' WHERE dept_id = :dept';
    $st = $pdo->prepare("SELECT COUNT(*) FROM stocks{$stocksWhere}");
    $st->execute($stocksParams);
    $stats['stocks'] = (int) $st->fetchColumn();

    $productsWhere = $isCrossDept ? '' : ' WHERE dept_id = :dept';
    $st = $pdo->prepare("SELECT COUNT(*) FROM products{$productsWhere}");
    $st->execute($stocksParams);
    $stats['products'] = (int) $st->fetchColumn();

    if ($isCrossDept) {
        $st = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1');
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

    api_dashboard_populate_edit_requests($pdo, $user, $canApproveEditRequests, $payload);

    if (ims_user_can($user, 'reports.view')) {
        api_dashboard_populate_financials($pdo, $payload);
    }

    api_ok($payload);
}

/**
 * @param array<string, mixed> $payload
 */
function api_dashboard_populate_edit_requests(PDO $pdo, array $user, bool $canApprove, array &$payload): void
{
    if ($canApprove) {
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

        return;
    }

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

/**
 * @param array<string, mixed> $payload
 */
function api_dashboard_populate_financials(PDO $pdo, array &$payload): void
{
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
}

function api_dashboard_inventory_snapshot(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!in_array($role, ['executive', 'prod_manager', 'hr'], true)) {
        api_fail('Not available for this role.', 403);
    }

    $period = isset($_GET['period']) ? (string) $_GET['period'] : 'all';
    $from = isset($_GET['from']) ? (string) $_GET['from'] : null;
    $to = isset($_GET['to']) ? (string) $_GET['to'] : null;

    try {
        $snapshot = dashboard_inventory_snapshot(ims_pdo(), $period, $from, $to);
    } catch (InvalidArgumentException $e) {
        api_fail($e->getMessage(), 422);
    }

    api_ok($snapshot);
}

function api_dashboard_shipped_count_in_range(PDO $pdo, array $range): int
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM online_order_parcels
         WHERE status = "shipped" AND ship_date IS NOT NULL
         AND ship_date >= ? AND ship_date <= ?'
    );
    $st->execute([$range['from'], $range['to']]);

    return (int) $st->fetchColumn();
}

function api_dashboard_rts_opened_in_range(PDO $pdo, array $range): int
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM rts_cases
         WHERE voided_at IS NULL AND date_opened >= ? AND date_opened <= ?'
    );
    $st->execute([$range['from'], $range['to']]);

    return (int) $st->fetchColumn();
}

function api_dashboard_shipped_summary(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if ((string) $user['role'] !== 'executive') {
        api_fail('Not available for this role.', 403);
    }

    $period = isset($_GET['period']) ? (string) $_GET['period'] : 'day';
    if (!in_array($period, ['day', 'week', 'month'], true)) {
        api_fail('Invalid period. Use day, week, or month.', 422);
    }

    try {
        $range = dashboard_inventory_resolve_range($period, null, null);
    } catch (InvalidArgumentException $e) {
        api_fail($e->getMessage(), 422);
    }

    if ($range === null) {
        api_fail('Invalid period range.', 422);
    }

    $pdo = ims_pdo();
    $payload = [
        'filter' => $range,
        'shipped' => 0,
        'packing' => 0,
        'rts_opened' => 0,
        'cancelled_or_hold' => 0,
    ];

    try {
        $payload['shipped'] = api_dashboard_shipped_count_in_range($pdo, $range);
        $payload['rts_opened'] = api_dashboard_rts_opened_in_range($pdo, $range);

        $st = $pdo->query('SELECT COUNT(*) FROM online_order_parcels WHERE status = "packing"');
        $payload['packing'] = $st ? (int) $st->fetchColumn() : 0;

        $st = $pdo->query(
            'SELECT COUNT(*) FROM online_order_parcels WHERE status IN ("cancelled", "on_hold")'
        );
        $payload['cancelled_or_hold'] = $st ? (int) $st->fetchColumn() : 0;
    } catch (Throwable) {
        // Tables may be missing on older installs.
    }

    api_ok($payload);
}
