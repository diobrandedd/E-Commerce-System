<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const IMS_ROLE_EXECUTIVE = 'executive';
const IMS_ROLE_PROD_MANAGER = 'prod_manager';
const IMS_ROLE_TL = 'tl';
const IMS_ROLE_STAFF = 'staff';
const IMS_ROLE_POS = 'pos';

/**
 * Start session once for auth middleware.
 */
function ims_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

/**
 * @return array{id:int,username:string,name:string,email:string,employee_id:string,role:string,dept_id:?int}|null
 */
function ims_current_user(): ?array
{
    ims_session_start();
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['username'] ?? ''),
        'name' => (string) ($_SESSION['user_name'] ?? ''),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'employee_id' => (string) ($_SESSION['employee_id'] ?? ''),
        'role' => (string) $_SESSION['role'],
        'dept_id' => isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : null,
    ];
}

function ims_pos_authenticated(): bool
{
    ims_session_start();
    return !empty($_SESSION['pos_authenticated']);
}

/**
 * Login for regular users table.
 */
function ims_login_user(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    try {
        $pdo = ims_pdo();
        $st = $pdo->prepare(
            'SELECT u.id, u.username, u.name, u.email, u.employee_id, u.password_hash, u.dept_id, u.is_active, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? LIMIT 1'
        );
        $st->execute([$username]);
        $row = $st->fetch();
    } catch (Throwable $e) {
        return false;
    }

    if (!$row) {
        return false;
    }
    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return false;
    }
    if (!password_verify($password, (string) $row['password_hash'])) {
        return false;
    }

    ims_session_start();
    session_regenerate_id(true);
    unset($_SESSION['pos_authenticated'], $_SESSION['pos_pin_id'], $_SESSION['pos_label']);

    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['username'] = (string) $row['username'];
    $_SESSION['user_name'] = (string) $row['name'];
    $_SESSION['user_email'] = (string) $row['email'];
    $_SESSION['employee_id'] = (string) $row['employee_id'];
    $_SESSION['role'] = (string) $row['role_name'];
    $_SESSION['dept_id'] = isset($row['dept_id']) ? (int) $row['dept_id'] : null;

    return true;
}

/**
 * Separate POS PIN login session.
 */
function ims_login_pos(string $pin): bool
{
    $pin = trim($pin);
    if ($pin === '') {
        return false;
    }

    try {
        $pdo = ims_pdo();
        $st = $pdo->prepare('SELECT id, pin_hash, label, is_active FROM pos_pins WHERE is_active = 1 ORDER BY id ASC');
        $st->execute();
        $rows = $st->fetchAll();
    } catch (Throwable $e) {
        return false;
    }

    $match = null;
    foreach ($rows as $row) {
        if (password_verify($pin, (string) $row['pin_hash'])) {
            $match = $row;
            break;
        }
    }
    if ($match === null) {
        return false;
    }

    ims_session_start();
    session_regenerate_id(true);
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['employee_id'], $_SESSION['role'], $_SESSION['dept_id']);

    $_SESSION['pos_authenticated'] = true;
    $_SESSION['pos_pin_id'] = (int) $match['id'];
    $_SESSION['pos_label'] = (string) ($match['label'] ?? 'POS');

    return true;
}

function ims_logout(): void
{
    ims_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Role middleware for protected pages.
 *
 * @param list<string> $roles
 */
function requireRole(array $roles, string $loginRedirect = 'auth/login.php'): void
{
    ims_session_start();

    if (in_array(IMS_ROLE_POS, $roles, true)) {
        if (!ims_pos_authenticated()) {
            header('Location: ' . $loginRedirect, true, 302);
            exit;
        }
        return;
    }

    $user = ims_current_user();
    if ($user === null) {
        header('Location: ' . $loginRedirect, true, 302);
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function ims_redirect_dashboard_by_role(string $role): string
{
    return match ($role) {
        IMS_ROLE_EXECUTIVE => 'exec/dashboard.php',
        IMS_ROLE_PROD_MANAGER => 'prodmgr/dashboard.php',
        IMS_ROLE_TL, IMS_ROLE_STAFF => 'dept/home.php',
        IMS_ROLE_POS => 'pos/index.php',
        default => 'auth/login.php',
    };
}

function ims_is_executive(string $role): bool
{
    return $role === IMS_ROLE_EXECUTIVE;
}

function ims_is_prod_manager(string $role): bool
{
    return $role === IMS_ROLE_PROD_MANAGER;
}

function ims_is_tl(string $role): bool
{
    return $role === IMS_ROLE_TL;
}

function ims_is_staff(string $role): bool
{
    return $role === IMS_ROLE_STAFF;
}

function ims_can_view_all_departments(string $role): bool
{
    return ims_is_executive($role) || ims_is_prod_manager($role);
}

function ims_change_password(int $userId, string $currentPassword, string $newPassword): array
{
    if ($userId < 1) {
        return ['ok' => false, 'error' => 'Invalid session.'];
    }
    if ($currentPassword === '' || $newPassword === '') {
        return ['ok' => false, 'error' => 'Password fields are required.'];
    }
    if (strlen($newPassword) < 6) {
        return ['ok' => false, 'error' => 'New password must be at least 6 characters.'];
    }

    try {
        $pdo = ims_pdo();
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch();
        if (!$row) {
            return ['ok' => false, 'error' => 'User not found.'];
        }
        if (!password_verify($currentPassword, (string) $row['password_hash'])) {
            return ['ok' => false, 'error' => 'Current password is incorrect.'];
        }
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($newHash === false) {
            return ['ok' => false, 'error' => 'Could not hash new password.'];
        }
        $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, $userId]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not change password.'];
    }

    return ['ok' => true, 'error' => null];
}
