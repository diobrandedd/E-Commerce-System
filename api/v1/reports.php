<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * Read-only inventory reports API for Executive + Production Manager.
 *
 *   GET ?action=stock_movement&period=daily|weekly|monthly
 *   GET ?action=dept_summary
 *
 * Movement aggregates `stock_entries` (stock in) and `stock_deductions`
 * (stock out + deductions) for the requested period and returns both a
 * row breakdown and a chart-friendly bucketed payload.
 */

api_dispatch([
    'stock_movement' => 'api_reports_stock_movement',
    'dept_summary' => 'api_reports_dept_summary',
    'courier_summary' => 'api_reports_courier_summary',
    'pipeline_summary' => 'api_reports_pipeline_summary',
]);

function api_reports_require(): array
{
    return api_require_permission('reports.view');
}

function api_reports_stock_movement(): void
{
    api_method_allowed(['GET']);
    api_reports_require();
    $period = (string) ($_GET['period'] ?? 'monthly');
    if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
        $period = 'monthly';
    }
    $pdo = ims_pdo();

    [$bucketExpr, $windowExpr] = match ($period) {
        'daily' => ["DATE_FORMAT({col}, '%Y-%m-%d')", '(NOW() - INTERVAL 14 DAY)'],
        'weekly' => ["DATE_FORMAT({col}, '%x-W%v')", '(NOW() - INTERVAL 12 WEEK)'],
        default => ["DATE_FORMAT({col}, '%Y-%m')", '(NOW() - INTERVAL 12 MONTH)'],
    };
    $bucketIn = str_replace('{col}', 'se.created_at', $bucketExpr);
    $bucketOut = str_replace('{col}', 'sd.created_at', $bucketExpr);

    $movement = [];
    try {
        $st = $pdo->query(
            "SELECT {$bucketIn} AS bucket, d.id AS dept_id, d.name AS dept_name,
                    COALESCE(SUM(se.qty), 0) AS qty
             FROM stock_entries se
             INNER JOIN departments d ON d.id = se.dept_id
             WHERE se.created_at >= {$windowExpr}
             GROUP BY bucket, d.id, d.name
             ORDER BY bucket ASC, d.name ASC"
        );
        $rows = $st ? $st->fetchAll() : [];
        foreach ($rows as $r) {
            $key = $r['bucket'] . '|' . $r['dept_id'];
            $movement[$key]['bucket'] = (string) $r['bucket'];
            $movement[$key]['dept_id'] = (int) $r['dept_id'];
            $movement[$key]['dept_name'] = (string) $r['dept_name'];
            $movement[$key]['stock_in'] = (float) $r['qty'];
            $movement[$key]['stock_out'] = $movement[$key]['stock_out'] ?? 0.0;
            $movement[$key]['deductions'] = $movement[$key]['deductions'] ?? 0.0;
        }
    } catch (Throwable) {
        // table missing
    }

    try {
        $st = $pdo->query(
            "SELECT {$bucketOut} AS bucket, d.id AS dept_id, d.name AS dept_name,
                    COALESCE(SUM(sd.qty), 0) AS qty
             FROM stock_deductions sd
             INNER JOIN departments d ON d.id = sd.dept_id
             WHERE sd.created_at >= {$windowExpr}
             GROUP BY bucket, d.id, d.name
             ORDER BY bucket ASC, d.name ASC"
        );
        $rows = $st ? $st->fetchAll() : [];
        foreach ($rows as $r) {
            $key = $r['bucket'] . '|' . $r['dept_id'];
            $movement[$key]['bucket'] = $movement[$key]['bucket'] ?? (string) $r['bucket'];
            $movement[$key]['dept_id'] = $movement[$key]['dept_id'] ?? (int) $r['dept_id'];
            $movement[$key]['dept_name'] = $movement[$key]['dept_name'] ?? (string) $r['dept_name'];
            $movement[$key]['stock_in'] = $movement[$key]['stock_in'] ?? 0.0;
            // All deductions count as "stock out" for the report;
            // we surface them separately as "deductions" too so the operator
            // can see how much of stock_out was unplanned/breakage vs movement.
            $movement[$key]['stock_out'] = (float) $r['qty'];
            $movement[$key]['deductions'] = (float) $r['qty'];
        }
    } catch (Throwable) {
        // table missing
    }

    $rows = array_values($movement);
    usort($rows, static function (array $a, array $b): int {
        return [$a['bucket'], $a['dept_name']] <=> [$b['bucket'], $b['dept_name']];
    });
    foreach ($rows as &$r) {
        $r['period'] = $period;
        $r['balance'] = ($r['stock_in'] ?? 0) - ($r['stock_out'] ?? 0);
    }
    unset($r);

    // Build chart payload keyed by bucket with one column per department.
    $chartIndex = [];
    foreach ($rows as $r) {
        $bucket = $r['bucket'];
        if (!isset($chartIndex[$bucket])) {
            $chartIndex[$bucket] = ['bucket' => $bucket];
        }
        $chartIndex[$bucket][$r['dept_name']] = (float) $r['stock_in'];
    }
    $chart = array_values($chartIndex);

    api_ok(['rows' => $rows, 'chart' => $chart, 'period' => $period]);
}

function api_reports_dept_summary(): void
{
    api_method_allowed(['GET']);
    api_reports_require();
    $pdo = ims_pdo();
    $rows = [];
    try {
        $st = $pdo->query(
            "SELECT d.id AS dept_id, d.name AS dept_name,
                    (SELECT COUNT(*) FROM stocks WHERE dept_id = d.id) AS stocks,
                    (SELECT COUNT(*) FROM products WHERE dept_id = d.id) AS products,
                    (SELECT COUNT(*) FROM users WHERE dept_id = d.id AND is_active = 1) AS active_users,
                    COALESCE(
                        (SELECT SUM(qty) FROM stock_entries WHERE dept_id = d.id) -
                        COALESCE((SELECT SUM(qty) FROM stock_deductions WHERE dept_id = d.id), 0),
                        0
                    ) AS total_inventory_kg
             FROM departments d
             ORDER BY d.name ASC"
        );
        $rows = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        $rows = [];
    }

    foreach ($rows as &$r) {
        $r['stocks'] = (int) $r['stocks'];
        $r['products'] = (int) $r['products'];
        $r['active_users'] = (int) $r['active_users'];
        $r['total_inventory_kg'] = (float) $r['total_inventory_kg'];
    }
    unset($r);
    api_ok(['rows' => $rows]);
}

function api_reports_courier_summary(): void
{
    api_method_allowed(['GET']);
    api_reports_require();
    $pdo = ims_pdo();

    $filterMode = (string) ($_GET['filter_mode'] ?? 'month');
    if (!in_array($filterMode, ['date_range', 'month', 'year'], true)) {
        $filterMode = 'month';
    }

    $allowedStatuses = ['packing', 'shipped', 'on_hold', 'cancelled', 'returned', 'rts'];
    $status = trim((string) ($_GET['status'] ?? ''));
    if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
        $status = '';
    }

    $where = ['1=1'];
    $params = [];

    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    if ($filterMode === 'date_range') {
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }
    } elseif ($filterMode === 'year') {
        $year = trim((string) ($_GET['year'] ?? date('Y')));
        if (!preg_match('/^\d{4}$/', $year)) {
            $year = date('Y');
        }
        $where[] = 'YEAR(created_at) = ?';
        $params[] = (int) $year;
    } else {
        $month = trim((string) ($_GET['month'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $where[] = "DATE_FORMAT(created_at, '%Y-%m') = ?";
        $params[] = $month;
    }

    $counts = [
        'jnt' => 0,
        'spx' => 0,
        'flash' => 0,
    ];

    try {
        $sql = 'SELECT courier, COUNT(*) AS c
                FROM online_order_parcels
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY courier';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as $row) {
            $bucket = api_reports_courier_bucket((string) ($row['courier'] ?? ''));
            if ($bucket === null) {
                continue;
            }
            $counts[$bucket] += (int) ($row['c'] ?? 0);
        }
    } catch (Throwable) {
        // table missing / migration pending
    }

    $total = $counts['jnt'] + $counts['spx'] + $counts['flash'];
    $chartRaw = [
        ['key' => 'jnt', 'name' => 'J&T Express', 'count' => $counts['jnt']],
        ['key' => 'spx', 'name' => 'SPX Express', 'count' => $counts['spx']],
        ['key' => 'flash', 'name' => 'Flash Express', 'count' => $counts['flash']],
    ];
    $chart = [];
    foreach ($chartRaw as $entry) {
        $count = (int) $entry['count'];
        $chart[] = [
            'key' => $entry['key'],
            'name' => $entry['name'],
            'count' => $count,
            'percent' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
        ];
    }

    api_ok([
        'filter_mode' => $filterMode,
        'status' => $status,
        'total_shipments' => $total,
        'counts' => $counts,
        'chart' => $chart,
    ]);
}

function api_reports_pipeline_summary(): void
{
    api_method_allowed(['GET']);
    api_reports_require();
    $pdo = ims_pdo();

    $filterMode = (string) ($_GET['filter_mode'] ?? 'month');
    if (!in_array($filterMode, ['date_range', 'month', 'year'], true)) {
        $filterMode = 'month';
    }

    $allowedStatuses = ['packing', 'shipped', 'on_hold', 'cancelled', 'returned', 'rts'];
    $status = trim((string) ($_GET['status'] ?? ''));
    if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
        $status = '';
    }

    $where = ['1=1'];
    $params = [];

    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    if ($filterMode === 'date_range') {
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }
    } elseif ($filterMode === 'year') {
        $year = trim((string) ($_GET['year'] ?? date('Y')));
        if (!preg_match('/^\d{4}$/', $year)) {
            $year = date('Y');
        }
        $where[] = 'YEAR(created_at) = ?';
        $params[] = (int) $year;
    } else {
        $month = trim((string) ($_GET['month'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $where[] = "DATE_FORMAT(created_at, '%Y-%m') = ?";
        $params[] = $month;
    }

    $counts = [
        'partner' => 0,
        'vip' => 0,
        'shopee_spx' => 0,
        'tiktok' => 0,
    ];

    try {
        $sql = 'SELECT source_pipeline, COUNT(*) AS c
                FROM online_order_parcels
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY source_pipeline';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as $row) {
            $bucket = api_reports_pipeline_bucket((string) ($row['source_pipeline'] ?? ''));
            if ($bucket === null) {
                continue;
            }
            $counts[$bucket] += (int) ($row['c'] ?? 0);
        }
    } catch (Throwable) {
        // table missing / migration pending
    }

    $total = $counts['partner'] + $counts['vip'] + $counts['shopee_spx'] + $counts['tiktok'];
    $chartRaw = [
        ['key' => 'partner', 'name' => 'Partners', 'count' => $counts['partner']],
        ['key' => 'vip', 'name' => 'VIP', 'count' => $counts['vip']],
        ['key' => 'shopee_spx', 'name' => 'Shopee SPX', 'count' => $counts['shopee_spx']],
        ['key' => 'tiktok', 'name' => 'TikTok', 'count' => $counts['tiktok']],
    ];
    $chart = [];
    foreach ($chartRaw as $entry) {
        $count = (int) $entry['count'];
        $chart[] = [
            'key' => $entry['key'],
            'name' => $entry['name'],
            'count' => $count,
            'percent' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
        ];
    }

    api_ok([
        'filter_mode' => $filterMode,
        'status' => $status,
        'total_shipments' => $total,
        'counts' => $counts,
        'chart' => $chart,
    ]);
}

function api_reports_courier_bucket(string $courier): ?string
{
    require_once __DIR__ . '/../../includes/courier_detect.php';

    return rts_courier_label_bucket($courier);
}

function api_reports_pipeline_bucket(string $sourcePipeline): ?string
{
    $pipeline = strtolower(trim($sourcePipeline));
    if ($pipeline === '') {
        return null;
    }
    if (in_array($pipeline, ['partner', 'vip', 'shopee_spx', 'tiktok'], true)) {
        return $pipeline;
    }
    return null;
}
