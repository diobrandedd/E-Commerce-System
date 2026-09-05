<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/inventory_check.php';

function api_inventory_check_require_scan(): array
{
    $user = api_require_ims_user();
    if (!inventory_check_can_scan($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_inventory_check_require_view_all(): array
{
    $user = api_inventory_check_require_scan();
    if (!inventory_check_can_view_all($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_inventory_check_require_manage(): array
{
    $user = api_inventory_check_require_scan();
    if (!inventory_check_can_manage($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_inventory_check_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'context' => 'api_inventory_check_context',
    'event_open' => 'api_inventory_check_event_open',
    'event_close' => 'api_inventory_check_event_close',
    'event_cancel' => 'api_inventory_check_event_cancel',
    'event_list' => 'api_inventory_check_event_list',
    'scan' => 'api_inventory_check_scan',
    'scan_update' => 'api_inventory_check_scan_update',
    'scan_remove' => 'api_inventory_check_scan_remove',
    'summary' => 'api_inventory_check_summary',
    'scan_log' => 'api_inventory_check_scan_log',
    'compare_preview' => 'api_inventory_check_compare_preview',
    'compare_run' => 'api_inventory_check_compare_run',
    'compare_get' => 'api_inventory_check_compare_get',
    'compare_list' => 'api_inventory_check_compare_list',
    'finalize' => 'api_inventory_check_finalize',
]);

function api_inventory_check_context(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $pdo = ims_pdo();
    $event = inventory_check_event_current($pdo);
    $viewAll = inventory_check_can_view_all($user);

    api_ok([
        'can_view_all' => $viewAll,
        'can_manage' => inventory_check_can_manage($user),
        'user_id' => (int) $user['id'],
        'event' => $event,
        'summary' => $event !== null
            ? inventory_check_summary($pdo, (int) $event['id'], $viewAll ? null : (int) $user['id'])
            : null,
    ]);
}

function api_inventory_check_event_open(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_manage();
    $in = api_input();
    try {
        api_ok(['event' => inventory_check_event_open(
            ims_pdo(),
            (int) $user['id'],
            isset($in['note']) ? (string) $in['note'] : null
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_event_close(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_manage();
    $in = api_input();
    try {
        api_ok(['event' => inventory_check_event_close(
            ims_pdo(),
            (int) $user['id'],
            (int) ($in['event_id'] ?? 0)
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_event_cancel(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    api_inventory_check_require_manage();
    $in = api_input();
    try {
        inventory_check_event_cancel(ims_pdo(), (int) ($in['event_id'] ?? 0));
        api_ok(['cancelled' => true]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_event_list(): void
{
    api_method_allowed(['GET']);
    api_inventory_check_require_view_all();
    api_ok(inventory_check_event_list(
        ims_pdo(),
        (int) ($_GET['page'] ?? 1),
        (int) ($_GET['per_page'] ?? 25)
    ));
}

function api_inventory_check_scan(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_scan();
    $in = api_input();
    $pdo = ims_pdo();
    $eventId = (int) ($in['event_id'] ?? 0);
    try {
        $res = inventory_check_scan(
            $pdo,
            $eventId,
            (int) $user['id'],
            (string) ($in['code'] ?? ''),
            isset($in['qty']) ? (int) $in['qty'] : null,
            isset($in['batch_no']) ? (string) $in['batch_no'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
        return;
    }

    api_ok([
        'result' => $res['result'],
        'message' => $res['message'],
        'scan' => $res['scan'],
        'summary' => inventory_check_summary(
            $pdo,
            $eventId,
            inventory_check_can_view_all($user) ? null : (int) $user['id']
        ),
    ]);
}

function api_inventory_check_scan_update(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_scan();
    $in = api_input();
    $pdo = ims_pdo();
    try {
        $scan = inventory_check_scan_update(
            $pdo,
            (int) ($in['scan_id'] ?? 0),
            (int) $user['id'],
            inventory_check_can_manage($user),
            isset($in['qty']) ? (int) $in['qty'] : null,
            isset($in['batch_no']) ? (string) $in['batch_no'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
        return;
    }

    $eventId = (int) ($in['event_id'] ?? 0);
    if ($eventId < 1 && isset($scan['id'])) {
        $st = $pdo->prepare('SELECT event_id FROM inventory_check_scans WHERE id = ?');
        $st->execute([(int) ($in['scan_id'] ?? 0)]);
        $eventId = (int) $st->fetchColumn();
    }

    api_ok([
        'scan' => $scan,
        'summary' => $eventId > 0
            ? inventory_check_summary(
                $pdo,
                $eventId,
                inventory_check_can_view_all($user) ? null : (int) $user['id']
            )
            : null,
    ]);
}

function api_inventory_check_scan_remove(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_scan();
    $in = api_input();
    $pdo = ims_pdo();
    try {
        inventory_check_scan_remove(
            $pdo,
            (int) ($in['scan_id'] ?? 0),
            (int) $user['id'],
            inventory_check_can_manage($user)
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
        return;
    }

    $eventId = (int) ($in['event_id'] ?? 0);
    api_ok([
        'summary' => $eventId > 0
            ? inventory_check_summary(
                $pdo,
                $eventId,
                inventory_check_can_view_all($user) ? null : (int) $user['id']
            )
            : null,
    ]);
}

function api_inventory_check_summary(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $eventId = (int) ($_GET['event_id'] ?? 0);
    $viewAll = inventory_check_can_view_all($user);
    try {
        api_ok(inventory_check_summary(ims_pdo(), $eventId, $viewAll ? null : (int) $user['id']));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }
}

function api_inventory_check_scan_log(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $eventId = (int) ($_GET['event_id'] ?? 0);
    $filters = [
        'kind' => $_GET['kind'] ?? null,
        'product_key' => $_GET['product_key'] ?? null,
        'batch_no' => $_GET['batch_no'] ?? null,
        'size' => $_GET['size'] ?? null,
        'variant' => $_GET['variant'] ?? null,
        'packaging_row_id' => $_GET['packaging_row_id'] ?? null,
    ];
    if (!inventory_check_can_view_all($user)) {
        $filters['scanned_by'] = (int) $user['id'];
    }
    try {
        api_ok(inventory_check_scan_log(
            ims_pdo(),
            $eventId,
            $filters,
            (int) ($_GET['page'] ?? 1),
            (int) ($_GET['per_page'] ?? 50)
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_compare_preview(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    if (!inventory_check_can_manage($user) && !inventory_check_can_view_all($user)) {
        api_fail('Forbidden.', 403);
    }
    try {
        api_ok(inventory_check_compare_preview(ims_pdo(), (int) ($_GET['event_id'] ?? 0)));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_compare_run(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_view_all();
    $in = api_input();
    try {
        api_ok(inventory_check_compare_run(
            ims_pdo(),
            (int) ($in['event_id'] ?? 0),
            (int) $user['id']
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_finalize(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_manage();
    $in = api_input();
    $pdo = ims_pdo();
    $eventId = (int) ($in['event_id'] ?? 0);
    try {
        $compare = inventory_check_compare_run($pdo, $eventId, (int) $user['id']);
        $event = inventory_check_event_close($pdo, (int) $user['id'], $eventId);
        api_ok([
            'event' => $event,
            'compare' => $compare,
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_compare_get(): void
{
    api_method_allowed(['GET']);
    api_inventory_check_require_view_all();
    try {
        api_ok(inventory_check_compare_get(ims_pdo(), (int) ($_GET['run_id'] ?? 0)));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }
}

function api_inventory_check_compare_list(): void
{
    api_method_allowed(['GET']);
    api_inventory_check_require_view_all();
    api_ok(['runs' => inventory_check_compare_list(ims_pdo(), (int) ($_GET['event_id'] ?? 0))]);
}
