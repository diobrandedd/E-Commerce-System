<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const RTS_ROLE_ADMIN = 'admin';
const RTS_ROLE_DELIVERY = 'delivery';

function rts_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

/** @return array{id:int,username:string,role:string}|null */
function rts_auth_user(): ?array
{
    rts_session_start();
    $s = $_SESSION['rts_auth'] ?? null;
    if (!is_array($s) || !isset($s['id'], $s['username'], $s['role'])) {
        return null;
    }
    return [
        'id' => (int) $s['id'],
        'username' => (string) $s['username'],
        'role' => (string) $s['role'],
    ];
}

function rts_auth_login(string $username, string $password, string $requiredRole): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }
    try {
        $pdo = rts_pdo();
    } catch (Throwable $e) {
        return false;
    }
    try {
        $st = $pdo->prepare('SELECT id, username, password_hash, role FROM app_users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $row = $st->fetch();
    } catch (Throwable $e) {
        return false;
    }
    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        return false;
    }
    $role = (string) $row['role'];
    if ($requiredRole === RTS_ROLE_ADMIN && $role !== RTS_ROLE_ADMIN) {
        return false;
    }
    if ($requiredRole === RTS_ROLE_DELIVERY && $role !== RTS_ROLE_DELIVERY) {
        return false;
    }
    rts_session_start();
    session_regenerate_id(true);
    $_SESSION['rts_auth'] = [
        'id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'role' => $role,
    ];
    return true;
}

function rts_auth_logout(): void
{
    rts_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * @param list<string> $roles Allowed roles (e.g. admin only, or admin+delivery)
 * @param string $loginRedirect Relative URL (e.g. login.php or deliveries/login.php)
 */
function rts_require_roles(array $roles, string $loginRedirect = 'login.php'): void
{
    $u = rts_auth_user();
    if ($u === null || !in_array($u['role'], $roles, true)) {
        header('Location: ' . $loginRedirect, true, 302);
        exit;
    }
}

function rts_is_admin(): bool
{
    $u = rts_auth_user();
    return $u !== null && $u['role'] === RTS_ROLE_ADMIN;
}
