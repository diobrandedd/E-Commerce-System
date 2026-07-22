<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/partner_sold_reconcile.php';

function api_partner_sold_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'stash' => 'api_partner_sold_stash',
    'reconcile' => 'api_partner_sold_reconcile',
    'download' => 'api_partner_sold_download',
]);

function api_partner_sold_stash(): void
{
    api_method_allowed(['POST']);
    try {
        partner_sold_require_access();
        api_partner_sold_require_csrf();
        $token = partner_sold_stash_upload();
        api_ok(['token' => $token]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_partner_sold_reconcile(): void
{
    api_method_allowed(['POST']);
    try {
        partner_sold_require_access();
        api_partner_sold_require_csrf();
        $input = api_input();
        $result = partner_sold_reconcile(ims_pdo(), $input);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_partner_sold_download(): void
{
    api_method_allowed(['GET']);
    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token === '') {
        api_fail('token required.', 400);
    }
    partner_sold_serve_download($token);
}
