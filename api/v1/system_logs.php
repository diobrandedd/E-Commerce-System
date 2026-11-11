<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/system_activity_log.php';

api_dispatch([
    'list' => 'api_system_logs_list',
]);

function api_system_logs_list(): void
{
    api_method_allowed(['GET']);
    api_require_permission('logs.view');

    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 20);
    $category = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
    $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : null;
    $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : null;

    $result = system_activity_log_list(
        ims_pdo(),
        $page,
        $perPage,
        $category !== '' ? $category : null,
        $q !== '' ? $q : null,
        $dateFrom !== '' ? $dateFrom : null,
        $dateTo !== '' ? $dateTo : null
    );

    api_ok($result);
}
