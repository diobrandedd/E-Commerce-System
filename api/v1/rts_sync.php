<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/rts_inventory_sync.php';

api_dispatch([
    'run' => 'api_rts_sync_run',
    'status' => 'api_rts_sync_status',
]);

function api_rts_sync_require(): void
{
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $allowed = in_array($role, ['executive', 'prod_manager', 'hr', 'csr_tl', 'csr_staff'], true);
    if (!$allowed) {
        api_fail('Forbidden.', 403);
    }
}

function api_rts_sync_run(): void
{
    api_method_allowed(['POST', 'GET']);
    api_rts_sync_require();
    try {
        $result = rts_inventory_sync_run(ims_pdo());
        api_ok($result);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 500);
    }
}

function api_rts_sync_status(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    api_ok(rts_inventory_sync_status(ims_pdo()));
}
