<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/finished_leak_damage.php';

function api_finished_leak_damage_require_ops(): array
{
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'inventory.leak_damage.record') && !ims_user_can($user, 'inventory.leak_damage.view')) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

api_dispatch([
    'scan' => 'api_finished_leak_damage_scan',
    'history' => 'api_finished_leak_damage_history',
]);

function api_finished_leak_damage_scan(): void
{
    api_method_allowed(['POST']);
    api_require_csrf(api_input());
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'inventory.leak_damage.record')) {
        api_fail('Forbidden.', 403);
    }
    $in = api_input();
    $code = trim((string) ($in['code'] ?? ''));
    $reason = trim((string) ($in['reason'] ?? ''));
    $note = isset($in['note']) ? (string) $in['note'] : null;
    $qtyRaw = $in['qty'] ?? null;
    $qty = null;
    if ($qtyRaw !== null && $qtyRaw !== '') {
        $qty = (int) $qtyRaw;
    }

    try {
        $result = finished_leak_damage_scan(ims_pdo(), (int) $user['id'], $code, $reason, $note, $qty);
        if (!empty($result['requires_qty'])) {
            api_ok($result);

            return;
        }
        api_ok(['row' => $result]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_finished_leak_damage_history(): void
{
    api_method_allowed(['GET']);
    api_finished_leak_damage_require_ops();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $filters = [
        'reason' => $_GET['reason'] ?? null,
        'product_key' => $_GET['product_key'] ?? null,
        'scan_date' => $_GET['scan_date'] ?? null,
    ];

    try {
        api_ok(finished_leak_damage_history(ims_pdo(), $page, FINISHED_LEAK_DAMAGE_HISTORY_PER_PAGE, $filters));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}
