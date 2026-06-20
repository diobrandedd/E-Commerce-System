<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/fulfillment_packing.php';

function api_fulfillment_packing_assert_issue(): array
{
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!material_inventory_role_can_issue_packing($role)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

api_dispatch([
    'packing_balances_list' => 'api_fulfillment_packing_balances_list',
    'packing_issue_create' => 'api_fulfillment_packing_issue_create',
    'packing_issues_list' => 'api_fulfillment_packing_issues_list',
    'packing_issue_estimate' => 'api_fulfillment_packing_issue_estimate',
    'packing_damage_report' => 'api_fulfillment_packing_damage_report',
    'packing_damage_list' => 'api_fulfillment_packing_damage_list',
]);

function api_fulfillment_packing_balances_list(): void
{
    api_method_allowed(['GET']);
    api_fulfillment_packing_assert_issue();
    $pdo = ims_pdo();
    $rows = fulfillment_packing_balances_list($pdo);
    api_ok(['rows' => $rows]);
}

function api_fulfillment_packing_issues_list(): void
{
    api_method_allowed(['GET']);
    api_fulfillment_packing_assert_issue();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(5, (int) ($_GET['per_page'] ?? 10)));
    $pdo = ims_pdo();
    $result = fulfillment_packing_issues_list($pdo, $page, $perPage);
    api_ok([
        'rows' => $result['rows'],
        'total' => $result['total'],
        'page' => $page,
        'per_page' => $perPage,
    ]);
}

function api_fulfillment_packing_issue_estimate(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_fulfillment_packing_assert_issue();

    $lines = $input['lines'] ?? [];
    if (!is_array($lines)) {
        api_fail('lines must be an array.', 422);
    }
    $normalized = [];
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $normalized[] = [
            'stock_id' => (int) ($line['stock_id'] ?? 0),
            'qty' => (float) ($line['qty'] ?? 0),
            'unit' => (string) ($line['unit'] ?? 'pcs'),
            'bubble_width_cm' => isset($line['bubble_width_cm']) ? (int) $line['bubble_width_cm'] : null,
        ];
    }
    $pdo = ims_pdo();
    api_ok(['estimate' => fulfillment_packing_estimate_parcels($pdo, $normalized)]);
}

function api_fulfillment_packing_issue_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_fulfillment_packing_assert_issue();

    $lines = $input['lines'] ?? [];
    if (!is_array($lines) || $lines === []) {
        api_fail('lines required.', 422);
    }
    $note = isset($input['note']) ? trim((string) $input['note']) : null;
    $normalized = [];
    foreach ($lines as $line) {
        if (!is_array($line)) {
            api_fail('Invalid line.', 422);
        }
        $normalized[] = [
            'stock_id' => (int) ($line['stock_id'] ?? 0),
            'qty' => (float) ($line['qty'] ?? 0),
            'unit' => (string) ($line['unit'] ?? 'pcs'),
            'bubble_width_cm' => isset($line['bubble_width_cm']) ? (int) $line['bubble_width_cm'] : null,
        ];
    }

    $pdo = ims_pdo();
    try {
        $result = fulfillment_packing_issue_create($pdo, (int) $user['id'], $normalized, $note);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_fulfillment_packing_damage_report(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_fulfillment_packing_assert_issue();

    $stockId = (int) ($input['stock_id'] ?? 0);
    $qty = (float) ($input['qty'] ?? 0);
    $note = trim((string) ($input['note'] ?? ''));
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }

    $qtyUnit = isset($input['qty_unit']) ? trim((string) $input['qty_unit']) : null;

    $pdo = ims_pdo();
    try {
        $result = fulfillment_packing_report_damage($pdo, (int) $user['id'], $stockId, $qty, $note, $qtyUnit);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_fulfillment_packing_damage_list(): void
{
    api_method_allowed(['GET']);
    api_fulfillment_packing_assert_issue();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(5, (int) ($_GET['per_page'] ?? 10)));
    $pdo = ims_pdo();
    $result = fulfillment_packing_damage_list($pdo, $page, $perPage);
    api_ok([
        'rows' => $result['rows'],
        'total' => $result['total'],
        'page' => $page,
        'per_page' => $perPage,
    ]);
}
