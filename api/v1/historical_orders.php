<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/historical_orders_import.php';

function api_historical_orders_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'stash' => 'api_historical_orders_stash',
    'preview' => 'api_historical_orders_preview',
    'commit' => 'api_historical_orders_commit',
]);

function api_historical_orders_stash(): void
{
    api_method_allowed(['POST']);
    try {
        historical_orders_require_access();
        api_historical_orders_require_csrf();
        $token = historical_orders_stash_upload();
        api_ok(['token' => $token]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_historical_orders_preview(): void
{
    api_method_allowed(['POST']);
    try {
        historical_orders_require_access();
        api_historical_orders_require_csrf();
        $input = api_input();
        $result = historical_orders_preview(ims_pdo(), $input);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    } catch (Throwable $e) {
        api_fail('Preview failed: ' . $e->getMessage(), 500);
    }
}

function api_historical_orders_commit(): void
{
    api_method_allowed(['POST']);
    try {
        historical_orders_require_access();
        api_historical_orders_require_csrf();
        $input = api_input();
        $result = historical_orders_commit(ims_pdo(), $input);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    } catch (Throwable $e) {
        api_fail('Commit failed: ' . $e->getMessage(), 500);
    }
}
