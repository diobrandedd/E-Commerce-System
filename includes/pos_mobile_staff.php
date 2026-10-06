<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Staff assigned to the mobile POS app.
 * App login: employee ID only (PIN).
 * Allowed: executives, or users listed in pos_mobile_staff.
 */
function pos_mobile_staff_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `pos_mobile_staff` (
            `user_id` INT UNSIGNED NOT NULL,
            `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `assigned_by` INT UNSIGNED NULL,
            PRIMARY KEY (`user_id`),
            KEY `idx_pos_mobile_staff_assigned` (`assigned_at`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

function pos_mobile_user_is_assigned(int $userId): bool
{
    if ($userId < 1) {
        return false;
    }
    try {
        $pdo = ims_pdo();
        pos_mobile_staff_ensure_schema($pdo);
        $st = $pdo->prepare('SELECT 1 FROM pos_mobile_staff WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        return (bool) $st->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

/**
 * Who may use the mobile POS app: executives, or staff assigned in Settings.
 */
function pos_mobile_user_can_login(int $userId, string $role): bool
{
    if ($userId < 1) {
        return false;
    }
    require_once __DIR__ . '/auth_check.php';
    if (ims_is_executive($role)) {
        return true;
    }

    return pos_mobile_user_is_assigned($userId);
}

/**
 * Authenticate mobile POS with employee ID only.
 * The ID identifies the staff member; no username is required.
 *
 * @return array{id:int,username:string,name:string,email:string,employee_id:string,role:string,dept_id:?int}|null
 */
function pos_mobile_authenticate(string $employeeId): ?array
{
    $employeeId = trim($employeeId);
    if ($employeeId === '') {
        return null;
    }
    $pdo = ims_pdo();
    $st = $pdo->prepare(
        'SELECT u.id, u.username, u.name, u.email, u.employee_id, u.dept_id, u.is_active, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.employee_id = ?
         LIMIT 1'
    );
    $st->execute([$employeeId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false || !(int) $row['is_active']) {
        return null;
    }
    $role = (string) $row['role_name'];
    if (!pos_mobile_user_can_login((int) $row['id'], $role)) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'name' => (string) $row['name'],
        'email' => (string) $row['email'],
        'employee_id' => trim((string) $row['employee_id']),
        'role' => $role,
        'dept_id' => $row['dept_id'] !== null ? (int) $row['dept_id'] : null,
    ];
}

/**
 * Step-up check: employee ID must belong to an active executive.
 * Does not create a session or token — caller keeps the current POS login.
 *
 * @return array{id:int,name:string,employee_id:string,role:string}|null
 */
function pos_mobile_verify_executive(string $employeeId): ?array
{
    $employeeId = trim($employeeId);
    if ($employeeId === '') {
        return null;
    }
    require_once __DIR__ . '/auth_check.php';
    $pdo = ims_pdo();
    $st = $pdo->prepare(
        'SELECT u.id, u.name, u.employee_id, u.is_active, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.employee_id = ?
         LIMIT 1'
    );
    $st->execute([$employeeId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false || !(int) $row['is_active']) {
        return null;
    }
    $role = (string) $row['role_name'];
    if (!ims_is_executive($role)) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'employee_id' => trim((string) $row['employee_id']),
        'role' => $role,
    ];
}

/** @return list<array{id:int,username:string,name:string,role:string,dept_name:?string,assigned:bool}> */
function pos_mobile_staff_candidates(PDO $pdo): array
{
    pos_mobile_staff_ensure_schema($pdo);
    $st = $pdo->query(
        'SELECT u.id, u.username, u.name, r.name AS role_name, d.name AS dept_name,
                (SELECT 1 FROM pos_mobile_staff m WHERE m.user_id = u.id LIMIT 1) AS assigned
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.dept_id
         WHERE u.is_active = 1
         ORDER BY u.name ASC'
    );
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'name' => (string) $row['name'],
            'role' => (string) $row['role_name'],
            'dept_name' => $row['dept_name'] !== null ? (string) $row['dept_name'] : null,
            'assigned' => (int) ($row['assigned'] ?? 0) === 1,
        ];
    }

    return $out;
}

/** @return list<array{id:int,username:string,name:string,role:string,dept_name:?string,assigned_at:string}> */
function pos_mobile_staff_list(PDO $pdo): array
{
    pos_mobile_staff_ensure_schema($pdo);
    $st = $pdo->query(
        'SELECT u.id, u.username, u.name, r.name AS role_name, d.name AS dept_name, m.assigned_at
         FROM pos_mobile_staff m
         INNER JOIN users u ON u.id = m.user_id
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.dept_id
         WHERE u.is_active = 1
         ORDER BY u.name ASC'
    );
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'name' => (string) $row['name'],
            'role' => (string) $row['role_name'],
            'dept_name' => $row['dept_name'] !== null ? (string) $row['dept_name'] : null,
            'assigned_at' => (string) $row['assigned_at'],
        ];
    }

    return $out;
}

function pos_mobile_staff_assign(PDO $pdo, int $userId, ?int $assignedBy): void
{
    pos_mobile_staff_ensure_schema($pdo);
    if ($userId < 1) {
        throw new RuntimeException('Invalid user.');
    }
    $st = $pdo->prepare('SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
    $st->execute([$userId]);
    if ($st->fetchColumn() === false) {
        throw new RuntimeException('User not found or inactive.');
    }
    $pdo->prepare(
        'INSERT INTO pos_mobile_staff (user_id, assigned_by) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP, assigned_by = VALUES(assigned_by)'
    )->execute([$userId, $assignedBy !== null && $assignedBy > 0 ? $assignedBy : null]);
}

function pos_mobile_staff_unassign(PDO $pdo, int $userId): void
{
    pos_mobile_staff_ensure_schema($pdo);
    if ($userId < 1) {
        throw new RuntimeException('Invalid user.');
    }
    $pdo->prepare('DELETE FROM pos_mobile_staff WHERE user_id = ?')->execute([$userId]);
}
