<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/order_remarks.php';

$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

if ($action === 'download') {
    try {
        order_remarks_serve_download(trim((string) ($_GET['token'] ?? '')));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 403);
    }
}

/**
 * Order remarks API (CSR TL + Executive).
 *
 *   POST multipart ?action=stash     — file (.xlsx)
 *   GET  ?action=parse&token=...     — optional profile_id=
 *   POST JSON ?action=export          — { token, remarks: string[] }
 *   GET  ?action=download&token=...
 *   GET  ?action=profiles             — list carrier profile labels
 */

function api_order_remarks_assert_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Not authenticated.', 401);
    }
    if (!order_remarks_user_can_access($user)) {
        api_fail('Forbidden.', 403);
    }
    return $user;
}

function api_order_remarks_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    $expected = api_csrf_token();
    if (!hash_equals($expected, $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'stash' => 'api_order_remarks_stash',
    'parse' => 'api_order_remarks_parse',
    'export' => 'api_order_remarks_export',
    'profiles' => 'api_order_remarks_profiles',
]);

function api_order_remarks_stash(): void
{
    api_method_allowed(['POST']);
    api_order_remarks_assert_access();
    api_order_remarks_require_csrf();

    try {
        $token = order_remarks_stash_upload();
        api_ok(['token' => $token]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_order_remarks_parse(): void
{
    api_method_allowed(['GET']);
    api_order_remarks_assert_access();

    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token === '') {
        api_fail('Missing token.', 400);
    }

    $profileId = trim((string) ($_GET['profile_id'] ?? ''));
    $override = $profileId !== '' ? $profileId : null;

    try {
        $result = order_remarks_parse_workbook($token, $override);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_order_remarks_export(): void
{
    api_method_allowed(['POST']);
    api_order_remarks_assert_access();
    api_order_remarks_require_csrf();

    $input = api_input();
    $token = trim((string) ($input['token'] ?? ''));
    $remarks = $input['remarks'] ?? null;

    if ($token === '') {
        api_fail('Missing token.', 400);
    }
    if (!is_array($remarks)) {
        api_fail('remarks must be an array of strings.', 400);
    }

    $cleanRemarks = [];
    foreach ($remarks as $r) {
        $cleanRemarks[] = is_string($r) ? $r : (string) $r;
    }

    try {
        $layout = $input['layout'] ?? null;
        $layoutArr = is_array($layout) ? $layout : null;
        $result = order_remarks_export_workbook($token, $cleanRemarks, $layoutArr);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_order_remarks_profiles(): void
{
    api_method_allowed(['GET']);
    api_order_remarks_assert_access();
    api_ok(['profiles' => order_remarks_profile_labels()]);
}
