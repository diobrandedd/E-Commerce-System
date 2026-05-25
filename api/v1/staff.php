<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * Staff management API — mirrors `exec/staff_management.php`.
 *
 *   GET  ?action=list             -> users + roles + departments
 *   POST ?action=create
 *   POST ?action=update
 *   POST ?action=reset_password
 */

api_dispatch([
    'list' => 'api_staff_list',
    'create' => 'api_staff_create',
    'update' => 'api_staff_update',
    'reset_password' => 'api_staff_reset_password',
]);

function api_staff_username_valid(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.-]{3,64}$/', $username);
}

function api_staff_employee_id_valid(string $eid): bool
{
    return (bool) preg_match('/^[0-9]{4,12}$/', $eid);
}

function api_staff_role_needs_dept(string $role): bool
{
    return $role === 'tl' || $role === 'staff';
}

function api_staff_serialize_user(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'name' => (string) $row['name'],
        'email' => (string) $row['email'],
        'employee_id' => (string) $row['employee_id'],
        'role' => (string) $row['role_name'],
        'role_id' => (int) $row['role_id'],
        'dept_id' => isset($row['dept_id']) && $row['dept_id'] !== null ? (int) $row['dept_id'] : null,
        'dept_name' => $row['dept_name'] ?? null,
        'is_active' => (int) $row['is_active'] === 1,
        'created_at' => (string) $row['created_at'],
    ];
}

function api_staff_list(): void
{
    api_method_allowed(['GET']);
    api_require_role(['executive']);
    $pdo = ims_pdo();

    $st = $pdo->query(
        "SELECT u.id, u.username, u.name, u.email, u.employee_id, u.is_active, u.created_at,
                u.role_id, r.name AS role_name, u.dept_id, d.name AS dept_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.dept_id
         WHERE r.name IN ('executive','prod_manager','tl','staff','hr')
         ORDER BY u.created_at DESC, u.id DESC"
    );
    $users = array_map('api_staff_serialize_user', $st ? $st->fetchAll() : []);

    $stRoles = $pdo->query(
        "SELECT id, name FROM roles WHERE name IN ('executive','prod_manager','tl','staff','hr')
         ORDER BY FIELD(name,'executive','prod_manager','tl','staff','hr')"
    );
    $roles = $stRoles ? array_map(fn($r) => ['id' => (int) $r['id'], 'name' => (string) $r['name']], $stRoles->fetchAll()) : [];

    $stDept = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    $departments = $stDept ? array_map(fn($d) => ['id' => (int) $d['id'], 'name' => (string) $d['name']], $stDept->fetchAll()) : [];

    api_ok([
        'users' => $users,
        'roles' => $roles,
        'departments' => $departments,
    ]);
}

function api_staff_validate_payload(array $input, array $roleIdByName): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $username = trim((string) ($input['username'] ?? ''));
    $employeeId = trim((string) ($input['employee_id'] ?? ''));
    $role = trim((string) ($input['role'] ?? ''));
    $deptId = (int) ($input['dept_id'] ?? 0);
    $isActive = (int) ($input['is_active'] ?? 1) === 1 ? 1 : 0;

    if ($name === '') {
        throw new RuntimeException('Name is required.');
    }
    if (!api_staff_username_valid($username)) {
        throw new RuntimeException('Username must be 3-64 chars (letters, numbers, _, -, .).');
    }
    if (!api_staff_employee_id_valid($employeeId)) {
        throw new RuntimeException('Employee ID must be 4-12 digits.');
    }
    if (!isset($roleIdByName[$role])) {
        throw new RuntimeException('Invalid role.');
    }
    if (api_staff_role_needs_dept($role)) {
        if ($deptId < 1) {
            throw new RuntimeException('Department is required for TL/Staff.');
        }
    } else {
        $deptId = 0;
    }

    return [
        'name' => $name,
        'username' => $username,
        'employee_id' => $employeeId,
        'role' => $role,
        'dept_id' => $deptId,
        'is_active' => $isActive,
    ];
}

function api_staff_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);

    $pdo = ims_pdo();
    $roleIdByName = [];
    $st = $pdo->query("SELECT id, name FROM roles WHERE name IN ('executive','prod_manager','tl','staff','hr')");
    foreach ($st ? $st->fetchAll() : [] as $r) {
        $roleIdByName[(string) $r['name']] = (int) $r['id'];
    }

    $data = api_staff_validate_payload($input, $roleIdByName);
    $hash = password_hash($data['employee_id'], PASSWORD_BCRYPT);
    if ($hash === false) {
        api_fail('Could not hash default password.', 500);
    }
    $email = strtolower($data['username']) . '@pyx.local';

    $ins = $pdo->prepare(
        'INSERT INTO users (username, name, email, employee_id, password_hash, role_id, dept_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $data['username'],
        $data['name'],
        $email,
        $data['employee_id'],
        $hash,
        $roleIdByName[$data['role']],
        $data['dept_id'] > 0 ? $data['dept_id'] : null,
        $data['is_active'],
    ]);

    api_ok(['id' => (int) $pdo->lastInsertId(), 'message' => 'User created. Default password is the employee ID.']);
}

function api_staff_update(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);

    $userId = (int) ($input['user_id'] ?? 0);
    if ($userId < 1) {
        api_fail('Invalid user id.', 422);
    }

    $pdo = ims_pdo();
    $roleIdByName = [];
    $st = $pdo->query("SELECT id, name FROM roles WHERE name IN ('executive','prod_manager','tl','staff','hr')");
    foreach ($st ? $st->fetchAll() : [] as $r) {
        $roleIdByName[(string) $r['name']] = (int) $r['id'];
    }

    $data = api_staff_validate_payload($input, $roleIdByName);
    $email = strtolower($data['username']) . '@pyx.local';
    $upd = $pdo->prepare(
        'UPDATE users
         SET username = ?, name = ?, email = ?, employee_id = ?, role_id = ?, dept_id = ?, is_active = ?
         WHERE id = ?'
    );
    $upd->execute([
        $data['username'],
        $data['name'],
        $email,
        $data['employee_id'],
        $roleIdByName[$data['role']],
        $data['dept_id'] > 0 ? $data['dept_id'] : null,
        $data['is_active'],
        $userId,
    ]);

    api_ok(['updated' => true]);
}

function api_staff_reset_password(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);

    $userId = (int) ($input['user_id'] ?? 0);
    if ($userId < 1) {
        api_fail('Invalid user id.', 422);
    }
    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch();
    if (!$row) {
        api_fail('User not found.', 404);
    }
    $eid = trim((string) $row['employee_id']);
    if (!api_staff_employee_id_valid($eid)) {
        api_fail('Cannot reset: invalid employee ID.', 422);
    }
    $hash = password_hash($eid, PASSWORD_BCRYPT);
    if ($hash === false) {
        api_fail('Could not hash default password.', 500);
    }
    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$hash, $userId]);
    api_ok(['reset' => true]);
}
