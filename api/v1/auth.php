<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/forwarding.php';
require_once dirname(__DIR__, 2) . '/includes/pos_mobile_staff.php';
require_once dirname(__DIR__, 2) . '/includes/api_tokens.php';

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
    'pos_staff_login' => 'api_auth_pos_staff_login',
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
    $payload['permissions'] = ims_user_permissions((int) $user['id']);

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
    $serialized = api_auth_serialize_user([
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'email' => $user['email'],
        'employee_id' => $user['employee_id'],
        'role' => $user['role'],
    ], $dept);
    $serialized['permissions'] = ims_user_permissions((int) $user['id']);

    api_ok([
        'authenticated' => true,
        'mode' => 'user',
        'csrf' => api_csrf_token(),
        'forwarding' => rts_forwarding_api_payload(),
        'user' => $serialized,
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

    $label = (string) ($_SESSION['pos_label'] ?? 'POS');
    $payload = [
        'authenticated' => true,
        'mode' => 'pos',
        'csrf' => api_csrf_token(),
        'forwarding' => rts_forwarding_api_payload(),
        'user' => [
            'id' => 0,
            'username' => 'pos',
            'name' => $label,
            'role' => 'pos',
            'php_role' => 'pos',
            'dept' => 'all',
            'dept_id' => null,
            'dept_name' => 'POS terminal',
        ],
    ];

    // Always issue a Bearer token so the mobile POS app can call APIs without cookies.
    try {
        $issued = pos_api_token_issue(ims_pdo(), [
            'pin_id' => (int) ($_SESSION['pos_pin_id'] ?? 0),
            'label' => $label,
        ]);
        $payload['token'] = $issued['token'];
        $payload['token_expires_at'] = $issued['expires_at'];
    } catch (Throwable $e) {
        // Session login still succeeds for the website; mobile can retry.
        $payload['token'] = null;
        $payload['token_expires_at'] = null;
        $payload['token_error'] = $e->getMessage();
    }

    api_ok($payload);
}

/**
 * Mobile POS: executives or assigned staff. Sign in with employee ID only.
 */
function api_auth_pos_staff_login(): void
{
    api_method_allowed(['POST']);
    require_once __DIR__ . '/../../includes/pos_mobile_staff.php';
    $input = api_input();
    // Accept pin / password / employee_id for the same field.
    $pin = trim((string) ($input['pin'] ?? $input['password'] ?? $input['employee_id'] ?? ''));
    if ($pin === '') {
        api_fail('Employee ID is required.', 422);
    }

    $user = pos_mobile_authenticate($pin);
    if ($user === null) {
        $pdo = ims_pdo();
        $st = $pdo->prepare(
            'SELECT u.id, u.is_active, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.employee_id = ?
             LIMIT 1'
        );
        $st->execute([$pin]);
        $probe = $st->fetch(PDO::FETCH_ASSOC);
        if ($probe !== false
            && (int) $probe['is_active'] === 1
            && !pos_mobile_user_can_login((int) $probe['id'], (string) $probe['role_name'])) {
            api_fail('This account is not allowed on Mobile POS. Ask an executive to assign you in Settings.', 403);
        }
        api_fail('Invalid employee ID.', 401);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['dept_id'] = $user['dept_id'];

    $dept = api_dept_lookup($user['dept_id'] ?? null);
    $serialized = api_auth_serialize_user([
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'email' => $user['email'],
        'employee_id' => $user['employee_id'],
        'role' => $user['role'],
    ], $dept);
    $serialized['permissions'] = ims_user_permissions((int) $user['id']);

    $payload = [
        'authenticated' => true,
        'mode' => 'user',
        'csrf' => api_csrf_token(),
        'forwarding' => rts_forwarding_api_payload(),
        'user' => $serialized,
    ];

    try {
        $issued = pos_api_token_issue(ims_pdo(), [
            'user_id' => (int) $user['id'],
            'label' => (string) $user['name'],
        ]);
        $payload['token'] = $issued['token'];
        $payload['token_expires_at'] = $issued['expires_at'];
    } catch (Throwable $e) {
        $payload['token'] = null;
        $payload['token_expires_at'] = null;
        $payload['token_error'] = $e->getMessage();
    }

    api_ok($payload);
}

function api_auth_logout(): void
{
    api_method_allowed(['POST']);
    $plain = (string) ($GLOBALS['api_bearer_token'] ?? pos_api_bearer_from_request());
    if ($plain !== '') {
        try {
            pos_api_token_revoke_plain(ims_pdo(), $plain);
        } catch (Throwable) {
            // ignore revoke failures
        }
    }
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
