<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/forwarding.php';

/**
 * Auth endpoint for the SPA.
 *
 * Actions:
 *   GET  ?action=me                 -> current user + csrf token
 *   POST ?action=login              -> {username, password}
 *   POST ?action=pos_login          -> {pin}
 *   POST ?action=logout             -> destroys both IMS + POS sessions
 *   POST ?action=change_password    -> {current, next}
 */

api_dispatch([
    'me' => 'api_auth_me',
    'login' => 'api_auth_login',
    'pos_login' => 'api_auth_pos_login',
    'logout' => 'api_auth_logout',
    'change_password' => 'api_auth_change_password',
]);

function api_auth_serialize_user(array $row, ?array $deptRow = null): array
{
    $deptKey = $deptRow['key'] ?? 'all';
    $role = api_normalize_role((string) $row['role'], $deptKey === 'all' ? null : $deptKey);
    return [
        'id' => (int) $row['id'],
        'username' => (string) ($row['username'] ?? ''),
        'name' => (string) ($row['name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'employee_id' => (string) ($row['employee_id'] ?? ''),
        'role' => $role,
        'php_role' => (string) ($row['role'] ?? ''),
        'dept' => $deptKey,
        'dept_id' => $deptRow['id'] ?? null,
        'dept_name' => $deptRow['name'] ?? 'All departments',
    ];
}

function api_auth_me(): void
{
    api_method_allowed(['GET']);
    $csrf = api_csrf_token();

    if (ims_pos_authenticated()) {
        api_ok([
            'authenticated' => true,
            'mode' => 'pos',
            'csrf' => $csrf,
            'forwarding' => rts_forwarding_api_payload(),
            'user' => [
                'id' => 0,
                'username' => 'pos',
                'name' => (string) ($_SESSION['pos_label'] ?? 'POS'),
                'role' => 'pos',
                'php_role' => 'pos',
                'dept' => 'all',
                'dept_id' => null,
                'dept_name' => 'POS terminal',
            ],
        ]);
    }

    $user = ims_current_user();
    if ($user === null) {
        api_ok([
            'authenticated' => false,
            'mode' => null,
            'csrf' => $csrf,
            'forwarding' => rts_forwarding_api_payload(),
            'user' => null,
        ]);
    }

    $dept = api_dept_lookup($user['dept_id'] ?? null);
    $payload = api_auth_serialize_user([
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'email' => $user['email'],
        'employee_id' => $user['employee_id'],
        'role' => $user['role'],
    ], $dept);

    api_ok([
        'authenticated' => true,
        'mode' => 'user',
        'csrf' => $csrf,
        'forwarding' => rts_forwarding_api_payload(),
        'user' => $payload,
    ]);
}

function api_auth_login(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    // login does not require CSRF because the user has no session yet
    $username = trim((string) ($input['username'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($username === '' || $password === '') {
        api_fail('Username and password are required.', 422);
    }
    if (!ims_login_user($username, $password)) {
        api_fail('Invalid credentials.', 401);
    }
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Login session could not be created.', 500);
    }
    $dept = api_dept_lookup($user['dept_id'] ?? null);
    api_ok([
        'authenticated' => true,
        'mode' => 'user',
        'csrf' => api_csrf_token(),
        'forwarding' => rts_forwarding_api_payload(),
        'user' => api_auth_serialize_user([
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'employee_id' => $user['employee_id'],
            'role' => $user['role'],
        ], $dept),
    ]);
}

function api_auth_pos_login(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    $pin = trim((string) ($input['pin'] ?? ''));
    if ($pin === '') {
        api_fail('PIN is required.', 422);
    }
    if (!ims_login_pos($pin)) {
        api_fail('Invalid PIN.', 401);
    }
    api_ok([
        'authenticated' => true,
        'mode' => 'pos',
        'csrf' => api_csrf_token(),
        'forwarding' => rts_forwarding_api_payload(),
        'user' => [
            'id' => 0,
            'username' => 'pos',
            'name' => (string) ($_SESSION['pos_label'] ?? 'POS'),
            'role' => 'pos',
            'php_role' => 'pos',
            'dept' => 'all',
            'dept_id' => null,
            'dept_name' => 'POS terminal',
        ],
    ]);
}

function api_auth_logout(): void
{
    api_method_allowed(['POST']);
    ims_logout();
    api_ok(['authenticated' => false]);
}

function api_auth_change_password(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $current = (string) ($input['current'] ?? '');
    $next = (string) ($input['next'] ?? '');
    $result = ims_change_password((int) $user['id'], $current, $next);
    if (!($result['ok'] ?? false)) {
        api_fail((string) ($result['error'] ?? 'Could not change password.'), 400);
    }
    api_ok(['changed' => true]);
}
