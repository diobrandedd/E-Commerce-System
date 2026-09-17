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

/**
 * Owner may complete/cancel their private session; manage/view_all may act on any.
 *
 * @param array{id:int,role?:string} $user
 */
function api_inventory_check_require_event_access(array $user, int $eventId): array
{
    try {
        return inventory_check_event_require_access(ims_pdo(), $eventId, $user);
    } catch (Throwable $e) {
        if ($e->getMessage() === 'Forbidden.') {
            api_fail('Forbidden.', 403);
        }
        api_fail($e->getMessage(), 400);
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
    'compare_preview_merged' => 'api_inventory_check_compare_preview_merged',
    'compare_run' => 'api_inventory_check_compare_run',
    'compare_get' => 'api_inventory_check_compare_get',
    'compare_list' => 'api_inventory_check_compare_list',
    'applied_runs_list' => 'api_inventory_check_applied_runs_list',
    'finalize' => 'api_inventory_check_finalize',
    'finalize_merged' => 'api_inventory_check_finalize_merged',
]);

function api_inventory_check_context(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $pdo = ims_pdo();
    $event = inventory_check_event_current($pdo, (int) $user['id']);
    $viewAll = inventory_check_can_view_all($user);

    api_ok([
        'can_view_all' => $viewAll,
        'can_manage' => inventory_check_can_manage($user),
        'user_id' => (int) $user['id'],
        'event' => $event,
        'summary' => $event !== null
            ? inventory_check_summary($pdo, (int) $event['id'], null)
            : null,
    ]);
}

function api_inventory_check_event_open(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_scan();
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
    $user = api_inventory_check_require_scan();
    $in = api_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    try {
        api_ok(['event' => inventory_check_event_close(
            ims_pdo(),
            (int) $user['id'],
            $eventId
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_event_cancel(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_scan();
    $in = api_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    try {
        inventory_check_event_cancel(ims_pdo(), $eventId);
        api_ok(['cancelled' => true]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_event_list(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $onlyMine = inventory_check_can_view_all($user) ? null : (int) $user['id'];
    api_ok(inventory_check_event_list(
        ims_pdo(),
        (int) ($_GET['page'] ?? 1),
        (int) ($_GET['per_page'] ?? 25),
        $onlyMine
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
            isset($in['batch_no']) ? (string) $in['batch_no'] : null,
            isset($in['bottling_date']) ? (string) $in['bottling_date'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
        return;
    }

    api_ok([
        'result' => $res['result'],
        'message' => $res['message'],
        'scan' => $res['scan'],
        'summary' => inventory_check_summary($pdo, $eventId, null),
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
            isset($in['batch_no']) ? (string) $in['batch_no'] : null,
            isset($in['bottling_date']) ? (string) $in['bottling_date'] : null
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
        'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,
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
        'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,
    ]);
}

function api_inventory_check_summary(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $eventId = (int) ($_GET['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    try {
        api_ok(inventory_check_summary(ims_pdo(), $eventId, null));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }
}

function api_inventory_check_scan_log(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $eventId = (int) ($_GET['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    $filters = [
        'kind' => $_GET['kind'] ?? null,
        'product_key' => $_GET['product_key'] ?? null,
        'batch_no' => $_GET['batch_no'] ?? null,
        'size' => $_GET['size'] ?? null,
        'variant' => $_GET['variant'] ?? null,
        'packaging_row_id' => $_GET['packaging_row_id'] ?? null,
    ];
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
    $eventId = (int) ($_GET['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    try {
        api_ok(inventory_check_compare_preview(ims_pdo(), $eventId));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_compare_preview_merged(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    api_inventory_check_require_view_all();
    $in = api_input();
    $ids = is_array($in['event_ids'] ?? null) ? $in['event_ids'] : [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (count($ids) < 1) {
        api_fail('Select at least one audit session.', 422);
    }
    $pdo = ims_pdo();
    foreach ($ids as $id) {
        $ev = inventory_check_event_get($pdo, $id);
        if ((string) ($ev['status'] ?? '') !== 'closed') {
            api_fail('Only closed audits can be merged.', 422);
        }
    }
    try {
        api_ok(inventory_check_compare_preview($pdo, $ids[0], $ids));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_compare_run(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_scan();
    $in = api_input();
    $eventId = (int) ($in['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    try {
        api_ok(inventory_check_compare_run(
            ims_pdo(),
            $eventId,
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
    $user = api_inventory_check_require_scan();
    $in = api_input();
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    api_require_admin_override_password($in);
    $pdo = ims_pdo();
    $eventId = (int) ($in['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    if (!inventory_check_can_manage($user) && !inventory_check_can_view_all($user)) {
        api_fail('Forbidden.', 403);
    }
    try {
        $result = inventory_check_finalize_with_sync($pdo, $user, [$eventId], true);
        api_ok($result);
    } catch (Throwable $e) {
        if ($e->getMessage() === 'Forbidden.') {
            api_fail('Forbidden.', 403);
        }
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_finalize_merged(): void
{
    api_method_allowed(['POST']);
    api_inventory_check_require_csrf();
    $user = api_inventory_check_require_view_all();
    $in = api_input();
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    api_require_admin_override_password($in);
    $rawIds = is_array($in['event_ids'] ?? null) ? $in['event_ids'] : [];
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
    if (count($eventIds) < 1) {
        api_fail('Select at least one closed audit.', 422);
    }
    $pdo = ims_pdo();
    foreach ($eventIds as $eid) {
        api_inventory_check_require_event_access($user, $eid);
        $ev = inventory_check_event_get($pdo, $eid);
        if ((string) ($ev['status'] ?? '') !== 'closed') {
            api_fail('Merged update only works on closed audits.', 422);
        }
    }
    try {
        $result = inventory_check_finalize_with_sync($pdo, $user, $eventIds, false);
        api_ok($result);
    } catch (Throwable $e) {
        if ($e->getMessage() === 'Forbidden.') {
            api_fail('Forbidden.', 403);
        }
        api_fail($e->getMessage(), 400);
    }
}

function api_inventory_check_compare_get(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $pdo = ims_pdo();
    try {
        $runId = (int) ($_GET['run_id'] ?? 0);
        $st = $pdo->prepare(
            'SELECT r.*, u.name AS run_by_name, ua.name AS stock_applied_by_name
             FROM inventory_check_compare_runs r
             LEFT JOIN users u ON u.id = r.run_by
             LEFT JOIN users ua ON ua.id = r.stock_applied_by
             WHERE r.id = ? LIMIT 1'
        );
        $st->execute([$runId]);
        $runRow = $st->fetch(PDO::FETCH_ASSOC);
        if ($runRow === false) {
            api_fail('Comparison run not found.', 404);
        }
        if (!inventory_check_user_can_access_compare_run($pdo, $user, $runRow)) {
            api_fail('Forbidden.', 403);
        }
        api_ok(inventory_check_compare_get($pdo, $runId));
    } catch (Throwable $e) {
        if ($e->getMessage() === 'Forbidden.') {
            api_fail('Forbidden.', 403);
        }
        api_fail($e->getMessage(), 404);
    }
}

function api_inventory_check_compare_list(): void
{
    api_method_allowed(['GET']);
    $user = api_inventory_check_require_scan();
    $eventId = (int) ($_GET['event_id'] ?? 0);
    api_inventory_check_require_event_access($user, $eventId);
    api_ok(['runs' => inventory_check_compare_list(ims_pdo(), $eventId)]);
}

function api_inventory_check_applied_runs_list(): void
{
    api_method_allowed(['GET']);
    api_inventory_check_require_view_all();
    api_ok(inventory_check_applied_runs_list(
        ims_pdo(),
        (int) ($_GET['page'] ?? 1),
        (int) ($_GET['per_page'] ?? 25)
    ));
}
