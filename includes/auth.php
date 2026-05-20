<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const RTS_ROLE_ADMIN = 'admin';
const RTS_ROLE_EXECUTIVE = 'executive';
const RTS_ROLE_PRODUCTION_MANAGER = 'production_manager';
const RTS_ROLE_TEAM_LEADER = 'team_leader';
const RTS_ROLE_STAFF = 'staff';
const RTS_ROLE_DELIVERY = 'delivery';

const RTS_USER_STATUS_ACTIVE = 'active';
const RTS_USER_STATUS_INACTIVE = 'inactive';
const RTS_USER_STATUS_AWOL = 'awol';
const RTS_USER_STATUS_ON_LEAVE = 'on_leave';
const RTS_USER_STATUS_RESIGNED = 'resigned';

const RTS_DEPT_CSR = 'csr';
const RTS_DEPT_HONEY = 'honey';
const RTS_DEPT_CACAO_BRC = 'cacao_black_rice_coffee';
const RTS_DEPT_PARAGIS = 'paragis';

/** @return list<string> */
function rts_user_role_values(): array
{
    return [
        RTS_ROLE_EXECUTIVE,
        RTS_ROLE_PRODUCTION_MANAGER,
        RTS_ROLE_TEAM_LEADER,
        RTS_ROLE_STAFF,
        RTS_ROLE_DELIVERY,
        RTS_ROLE_ADMIN, // legacy compatibility
    ];
}

/** @return array<string, string> */
function rts_user_role_labels(): array
{
    return [
        RTS_ROLE_EXECUTIVE => 'Executive',
        RTS_ROLE_PRODUCTION_MANAGER => 'Production Manager',
        RTS_ROLE_TEAM_LEADER => 'Team Leader',
        RTS_ROLE_STAFF => 'Staff',
        RTS_ROLE_DELIVERY => 'Delivery',
        RTS_ROLE_ADMIN => 'Admin (legacy)',
    ];
}

/** @return list<string> */
function rts_user_status_values(): array
{
    return [
        RTS_USER_STATUS_ACTIVE,
        RTS_USER_STATUS_INACTIVE,
        RTS_USER_STATUS_AWOL,
        RTS_USER_STATUS_ON_LEAVE,
        RTS_USER_STATUS_RESIGNED,
    ];
}

/** @return array<string, string> */
function rts_user_status_labels(): array
{
    return [
        RTS_USER_STATUS_ACTIVE => 'Active',
        RTS_USER_STATUS_INACTIVE => 'Inactive',
        RTS_USER_STATUS_AWOL => 'AWOL',
        RTS_USER_STATUS_ON_LEAVE => 'On leave',
        RTS_USER_STATUS_RESIGNED => 'Resigned',
    ];
}

/** @return list<string> */
function rts_department_values(): array
{
    return [
        RTS_DEPT_CSR,
        RTS_DEPT_HONEY,
        RTS_DEPT_CACAO_BRC,
        RTS_DEPT_PARAGIS,
    ];
}

/** @return array<string, string> */
function rts_department_labels(): array
{
    return [
        RTS_DEPT_CSR => 'Customer Service Representative (CSR)',
        RTS_DEPT_HONEY => 'Honey Department',
        RTS_DEPT_CACAO_BRC => 'Cacao / Black Rice Coffee Department',
        RTS_DEPT_PARAGIS => 'Paragis Department',
    ];
}

function rts_role_valid(string $role): bool
{
    return in_array($role, rts_user_role_values(), true);
}

function rts_status_valid(string $status): bool
{
    return in_array($status, rts_user_status_values(), true);
}

function rts_department_valid(string $department): bool
{
    return in_array($department, rts_department_values(), true);
}

function rts_role_is_executive(string $role): bool
{
    return $role === RTS_ROLE_EXECUTIVE || $role === RTS_ROLE_ADMIN;
}

function rts_role_can_access_rts_admin(string $role): bool
{
    return rts_role_is_executive($role) || $role === RTS_ROLE_PRODUCTION_MANAGER;
}

function rts_can_access_rts_admin(?array $user): bool
{
    if ($user === null) {
        return false;
    }
    return rts_role_can_access_rts_admin((string) ($user['role'] ?? ''));
}

function rts_login_redirect_by_role(string $role): string
{
    if ($role === RTS_ROLE_ADMIN || $role === RTS_ROLE_PRODUCTION_MANAGER) {
        return 'dashboard.php';
    }
    if ($role === RTS_ROLE_EXECUTIVE) {
        return 'executive.php';
    }
    if ($role === RTS_ROLE_DELIVERY) {
        return 'deliveries/index.php';
    }
    return 'portal.php';
}

/** Whether app_users has a status column (older DBs may not). */
function rts_app_users_has_status_column(PDO $pdo): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM app_users LIKE 'status'");
        $has = $st !== false && $st->fetch() !== false;
    } catch (Throwable) {
        $has = false;
    }

    return $has;
}

/**
 * app_users.id for delivery_batches.submitted_by_user_id (logged-in user, else shared delivery account).
 */
function rts_delivery_submitter_user_id(?PDO $pdo = null): int
{
    $auth = rts_auth_user();
    if ($auth !== null) {
        $id = (int) ($auth['id'] ?? 0);
        if ($id > 0) {
            return $id;
        }
    }

    static $guestId = null;
    if ($guestId !== null && $guestId > 0) {
        return $guestId;
    }

    if ($pdo === null) {
        $pdo = rts_pdo();
    }

    $filterActive = rts_app_users_has_status_column($pdo);

    if ($filterActive) {
        $st = $pdo->prepare(
            "SELECT id FROM app_users WHERE username = ? AND status = 'active' LIMIT 1"
        );
    } else {
        $st = $pdo->prepare('SELECT id FROM app_users WHERE username = ? LIMIT 1');
    }
    $st->execute(['delivery']);
    $guestId = (int) ($st->fetchColumn() ?: 0);
    if ($guestId > 0) {
        return $guestId;
    }

    if ($filterActive) {
        $st = $pdo->query("SELECT id FROM app_users WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    } else {
        $st = $pdo->query('SELECT id FROM app_users ORDER BY id ASC LIMIT 1');
    }
    $guestId = (int) ($st ? $st->fetchColumn() : 0);

    return $guestId > 0 ? $guestId : 0;
}

function rts_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

/** @return array{id:int,username:string,role:string,full_name:string,department:?string,status:string}|null */
function rts_auth_user(): ?array
{
    rts_session_start();
    $s = $_SESSION['rts_auth'] ?? null;
    if (!is_array($s) || !isset($s['id'], $s['username'], $s['role'])) {
        return null;
    }
    $status = isset($s['status']) ? (string) $s['status'] : RTS_USER_STATUS_ACTIVE;
    if (!rts_status_valid($status)) {
        $status = RTS_USER_STATUS_ACTIVE;
    }
    $department = isset($s['department']) ? trim((string) $s['department']) : '';
    if ($department !== '' && !rts_department_valid($department)) {
        $department = '';
    }
    return [
        'id' => (int) $s['id'],
        'username' => (string) $s['username'],
        'role' => (string) $s['role'],
        'full_name' => isset($s['full_name']) ? (string) $s['full_name'] : (string) $s['username'],
        'department' => $department === '' ? null : $department,
        'status' => $status,
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
        $st = $pdo->prepare('SELECT * FROM app_users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $row = $st->fetch();
    } catch (Throwable $e) {
        return false;
    }
    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        return false;
    }
    $role = isset($row['role']) ? (string) $row['role'] : '';
    if (!rts_role_valid($role)) {
        return false;
    }
    $status = isset($row['status']) ? (string) $row['status'] : RTS_USER_STATUS_ACTIVE;
    if (!rts_status_valid($status)) {
        $status = RTS_USER_STATUS_ACTIVE;
    }
    if ($status !== RTS_USER_STATUS_ACTIVE) {
        return false;
    }
    if ($requiredRole === RTS_ROLE_ADMIN && !rts_role_can_access_rts_admin($role)) {
        return false;
    }
    if ($requiredRole === RTS_ROLE_EXECUTIVE && !rts_role_is_executive($role)) {
        return false;
    }
    if ($requiredRole === RTS_ROLE_DELIVERY && $role !== RTS_ROLE_DELIVERY) {
        return false;
    }
    $department = isset($row['department']) ? trim((string) $row['department']) : '';
    if ($department !== '' && !rts_department_valid($department)) {
        $department = '';
    }
    rts_session_start();
    session_regenerate_id(true);
    $_SESSION['rts_auth'] = [
        'id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'role' => $role,
        'full_name' => isset($row['full_name']) ? (string) $row['full_name'] : (string) $row['username'],
        'department' => $department === '' ? null : $department,
        'status' => $status,
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
    if ($u === null) {
        header('Location: ' . $loginRedirect, true, 302);
        exit;
    }
    if (in_array(RTS_ROLE_ADMIN, $roles, true) && rts_role_can_access_rts_admin($u['role'])) {
        return;
    }
    if (in_array(RTS_ROLE_EXECUTIVE, $roles, true) && rts_role_is_executive($u['role'])) {
        return;
    }
    if (!in_array($u['role'], $roles, true)) {
        header('Location: ' . $loginRedirect, true, 302);
        exit;
    }
}

function rts_is_admin(): bool
{
    $u = rts_auth_user();
    return $u !== null && rts_role_is_executive((string) $u['role']);
}
