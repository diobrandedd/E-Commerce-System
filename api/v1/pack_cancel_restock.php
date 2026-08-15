<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/pack_cancel_restock.php';

function api_pack_cancel_require_user(): array
{
    return api_require_ims_user();
}

function api_pack_cancel_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

function api_pack_cancel_require_ops(): array
{
    $user = api_pack_cancel_require_user();
    $role = (string) $user['role'];
    if (!in_array($role, ['executive', 'prod_manager', 'admin', 'tl', 'staff'], true)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

api_dispatch([
    'start' => 'api_pack_cancel_start',
    'get' => 'api_pack_cancel_get',
    'scan' => 'api_pack_cancel_scan',
    'confirm' => 'api_pack_cancel_confirm',
    'discard' => 'api_pack_cancel_discard',
    'list' => 'api_pack_cancel_list',
]);

function api_pack_cancel_start(): void
{
    api_method_allowed(['POST']);
    api_pack_cancel_require_csrf();
    $user = api_pack_cancel_require_ops();
    api_ok(['session' => pack_cancel_restock_start(ims_pdo(), (int) $user['id'])]);
}

function api_pack_cancel_get(): void
{
    api_method_allowed(['GET']);
    api_pack_cancel_require_ops();
    $id = (int) ($_GET['id'] ?? 0);
    try {
        api_ok(['session' => pack_cancel_restock_get(ims_pdo(), $id)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_pack_cancel_scan(): void
{
    api_method_allowed(['POST']);
    api_pack_cancel_require_csrf();
    api_pack_cancel_require_ops();
    $in = api_input();
    try {
        api_ok(pack_cancel_restock_scan(
            ims_pdo(),
            (int) ($in['session_id'] ?? 0),
            (string) ($in['code'] ?? '')
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_pack_cancel_confirm(): void
{
    api_method_allowed(['POST']);
    api_pack_cancel_require_csrf();
    $user = api_pack_cancel_require_ops();
    $in = api_input();
    try {
        api_ok(['session' => pack_cancel_restock_confirm(
            ims_pdo(),
            (int) $user['id'],
            (int) ($in['session_id'] ?? 0)
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_pack_cancel_discard(): void
{
    api_method_allowed(['POST']);
    api_pack_cancel_require_csrf();
    api_pack_cancel_require_ops();
    $in = api_input();
    pack_cancel_restock_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));
    api_ok(['ok' => true]);
}

function api_pack_cancel_list(): void
{
    api_method_allowed(['GET']);
    api_pack_cancel_require_ops();
    try {
        api_ok(pack_cancel_restock_list(
            ims_pdo(),
            isset($_GET['from']) ? (string) $_GET['from'] : null,
            isset($_GET['to']) ? (string) $_GET['to'] : null,
            isset($_GET['product_key']) ? (string) $_GET['product_key'] : null,
            (int) ($_GET['page'] ?? 1)
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}
