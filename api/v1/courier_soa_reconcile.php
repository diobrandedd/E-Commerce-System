<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/courier_soa_reconcile.php';

function api_courier_soa_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'stash' => 'api_courier_soa_stash',
    'reconcile' => 'api_courier_soa_reconcile',
    'download' => 'api_courier_soa_download',
]);

function api_courier_soa_stash(): void
{
    api_method_allowed(['POST']);
    try {
        courier_soa_require_access();
        api_courier_soa_require_csrf();
        $token = courier_soa_stash_upload();
        api_ok(['token' => $token]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_courier_soa_reconcile(): void
{
    api_method_allowed(['POST']);
    try {
        courier_soa_require_access();
        api_courier_soa_require_csrf();
        $input = api_input();
        $result = courier_soa_reconcile(ims_pdo(), $input);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_courier_soa_download(): void
{
    api_method_allowed(['GET']);
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token === '') {
        api_fail('token required.', 400);
    }
    courier_soa_serve_download($token);
}
