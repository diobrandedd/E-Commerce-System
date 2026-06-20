<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/ims_exec_sidebar.php';
requireRole([IMS_ROLE_EXECUTIVE], '../auth/login.php');

ims_session_start();
$auth = ims_current_user();
$authId = (int) ($auth['id'] ?? 0);

$notice = '';
$error = '';

if (!isset($_SESSION['ims_csrf']) || !is_string($_SESSION['ims_csrf']) || $_SESSION['ims_csrf'] === '') {
    $_SESSION['ims_csrf'] = bin2hex(random_bytes(24));
}
$csrf = (string) $_SESSION['ims_csrf'];

function ims_sm_username_valid(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.-]{3,64}$/', $username);
}

function ims_sm_employee_id_valid(string $employeeId): bool
{
    return (bool) preg_match('/^[0-9]{4,12}$/', $employeeId);
}

function ims_sm_role_needs_dept(string $role): bool
{
    return $role === IMS_ROLE_TL || $role === IMS_ROLE_STAFF;
}

try {
    $pdo = ims_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database unavailable.';
    exit;
}

$roles = [];
$departments = [];
try {
    $stRoles = $pdo->query("SELECT id, name FROM roles WHERE name IN ('executive','prod_manager','tl','staff') ORDER BY FIELD(name,'executive','prod_manager','tl','staff')");
    $roles = $stRoles ? $stRoles->fetchAll() : [];
    $stDept = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    $departments = $stDept ? $stDept->fetchAll() : [];
} catch (Throwable $e) {
    $error = 'Could not load roles/departments. Run phase 1 migration.';
}

$roleIdByName = [];
foreach ($roles as $r) {
    $roleIdByName[(string) $r['name']] = (int) $r['id'];
}
$deptNameById = [];
foreach ($departments as $d) {
    $deptNameById[(int) $d['id']] = (string) $d['name'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedCsrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $error = 'Session expired. Refresh and try again.';
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));
        try {
            if ($action === 'create_user') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $employeeId = trim((string) ($_POST['employee_id'] ?? ''));
                $role = trim((string) ($_POST['role'] ?? ''));
                $deptId = (int) ($_POST['dept_id'] ?? 0);
                $isActive = (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

                if ($name === '') {
                    throw new RuntimeException('Name is required.');
                }
                if (!ims_sm_username_valid($username)) {
                    throw new RuntimeException('Username must be 3-64 chars (letters, numbers, underscore, dash, dot).');
                }
                if (!ims_sm_employee_id_valid($employeeId)) {
                    throw new RuntimeException('Employee ID must be 4-12 digits.');
                }
                if (!isset($roleIdByName[$role])) {
                    throw new RuntimeException('Invalid role.');
                }
                if (ims_sm_role_needs_dept($role) && $deptId < 1) {
                    throw new RuntimeException('Department is required for TL/Staff.');
                }
                if (!ims_sm_role_needs_dept($role)) {
                    $deptId = 0;
                }

                $hash = password_hash($employeeId, PASSWORD_BCRYPT);
                if ($hash === false) {
                    throw new RuntimeException('Could not hash default password.');
                }
                $emailSynth = strtolower($username) . '@pyx.local';

                $ins = $pdo->prepare(
                    'INSERT INTO users (username, name, email, employee_id, password_hash, role_id, dept_id, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $username,
                    $name,
                    $emailSynth,
                    $employeeId,
                    $hash,
                    $roleIdByName[$role],
                    $deptId > 0 ? $deptId : null,
                    $isActive,
                ]);
                $notice = 'User created. Default password is employee ID.';
            } elseif ($action === 'update_user') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                $name = trim((string) ($_POST['name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $employeeId = trim((string) ($_POST['employee_id'] ?? ''));
                $role = trim((string) ($_POST['role'] ?? ''));
                $deptId = (int) ($_POST['dept_id'] ?? 0);
                $isActive = (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0;

                if ($userId < 1) {
                    throw new RuntimeException('Invalid user.');
                }
                if ($name === '') {
                    throw new RuntimeException('Name is required.');
                }
                if (!ims_sm_username_valid($username)) {
                    throw new RuntimeException('Invalid username format.');
                }
                if (!ims_sm_employee_id_valid($employeeId)) {
                    throw new RuntimeException('Employee ID must be 4-12 digits.');
                }
                if (!isset($roleIdByName[$role])) {
                    throw new RuntimeException('Invalid role.');
                }
                if (ims_sm_role_needs_dept($role) && $deptId < 1) {
                    throw new RuntimeException('Department is required for TL/Staff.');
                }
                if (!ims_sm_role_needs_dept($role)) {
                    $deptId = 0;
                }

                $emailSynth = strtolower($username) . '@pyx.local';
                $upd = $pdo->prepare(
                    'UPDATE users
                     SET username = ?, name = ?, email = ?, employee_id = ?, role_id = ?, dept_id = ?, is_active = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $username,
                    $name,
                    $emailSynth,
                    $employeeId,
                    $roleIdByName[$role],
                    $deptId > 0 ? $deptId : null,
                    $isActive,
                    $userId,
                ]);
                $notice = 'User updated.';
            } elseif ($action === 'reset_default_password') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                if ($userId < 1) {
                    throw new RuntimeException('Invalid user.');
                }
                $st = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? LIMIT 1');
                $st->execute([$userId]);
                $row = $st->fetch();
                if (!$row) {
                    throw new RuntimeException('User not found.');
                }
                $employeeId = trim((string) ($row['employee_id'] ?? ''));
                if (!ims_sm_employee_id_valid($employeeId)) {
                    throw new RuntimeException('Cannot reset password: invalid employee ID.');
                }
                $hash = password_hash($employeeId, PASSWORD_BCRYPT);
                if ($hash === false) {
                    throw new RuntimeException('Could not hash default password.');
                }
                $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $upd->execute([$hash, $userId]);
                $notice = 'Password reset to default employee ID.';
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $error = 'Duplicate username, email, or employee ID.';
            } else {
                $error = 'Database save failed.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$users = [];
try {
    $stUsers = $pdo->query(
        'SELECT u.id, u.username, u.name, u.email, u.employee_id, u.is_active, u.created_at,
                r.name AS role_name, d.id AS dept_id, d.name AS dept_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.dept_id
         WHERE r.name IN ("executive","prod_manager","tl","staff")
         ORDER BY u.created_at DESC, u.id DESC'
    );
    $users = $stUsers ? $stUsers->fetchAll() : [];
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load users.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Executive Staff Management</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); }
    .layout { display:grid; grid-template-columns:260px 1fr; min-height:100vh; }
    .exec-sidebar { background:#fff9ef; border-right:1px solid var(--border); padding:1rem 0.85rem; }
    .exec-sidebar-title { margin:0 0 0.65rem; font-size:1rem; color:var(--muted); }
    .exec-sidebar-nav { display:flex; flex-direction:column; gap:0.35rem; }
    .exec-sidebar-link { display:flex; justify-content:space-between; align-items:center; text-decoration:none; border:1px solid var(--border); border-radius:8px; padding:0.5rem 0.65rem; background:#fffdfa; color:var(--text); font-weight:600; }
    .exec-sidebar-link.active { border-color:var(--accent); color:var(--accent); background:#fff7e8; }
    .exec-sidebar-badge { font-size:0.75rem; border:1px solid #d1b24f; border-radius:999px; padding:0.05rem 0.4rem; color:#7a5e05; background:#fff8de; }
    .content { padding:1rem; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:0.95rem 1rem; margin-bottom:0.9rem; }
    .row { display:grid; gap:0.65rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    @media (max-width:980px){ .row { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px){ .row { grid-template-columns:1fr; } }
    label { display:block; font-size:0.78rem; color:var(--muted); margin:0.25rem 0 0.15rem; }
    input, select, textarea { width:100%; padding:0.5rem 0.55rem; border:1px solid var(--border); border-radius:8px; background:#fffdfa; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.45rem 0.7rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .muted { color:var(--muted); }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
    .pill { display:inline-flex; align-items:center; font-size:0.75rem; border:1px solid var(--border); border-radius:999px; padding:0.14rem 0.48rem; background:#fff9ef; }
    .pill.active { border-color:#5aa46c; color:#1f6a33; background:#ecfff1; }
    .pill.inactive { border-color:#c88383; color:#8f2e2e; background:#fff0f0; }
    @media (max-width:960px) {
      .layout { grid-template-columns:1fr; }
      .exec-sidebar { border-right:0; border-bottom:1px solid var(--border); }
    }
  </style>
</head>
<body>
  <div class="layout">
    <?php ims_render_exec_sidebar('staff_management'); ?>
    <main class="content">
  <div class="card">
    <h1 style="margin:0 0 0.2rem;font-size:1.25rem;">Executive Staff Management</h1>
    <p class="muted" style="margin:0 0 0.55rem;">Signed in as <strong><?= htmlspecialchars((string) ($auth['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>. Default password for new/reset users is employee ID.</p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Add user</h2>
    <form method="post" action="staff_management.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="create_user">
      <div class="row">
        <div>
          <label for="name">Full name</label>
          <input id="name" name="name" required maxlength="120" placeholder="Noli Fin">
        </div>
        <div>
          <label for="username">Username (login)</label>
          <input id="username" name="username" required maxlength="64" placeholder="Noli_F">
        </div>
        <div>
          <label for="employee_id">Employee ID (default password)</label>
          <input id="employee_id" name="employee_id" required maxlength="12" inputmode="numeric" placeholder="025027">
        </div>
        <div>
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="dept_id">Department (required for TL/Staff)</label>
          <select id="dept_id" name="dept_id">
            <option value="0">— None —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars((string) $d['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="is_active">Status</label>
          <select id="is_active" name="is_active">
            <option value="1" selected>active</option>
            <option value="0">inactive</option>
          </select>
        </div>
        <div style="display:flex;align-items:flex-end;">
          <button class="btn primary" type="submit" style="width:100%;">Create user</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card">
    <table id="usersTable">
      <thead>
        <tr>
          <th>Name / username</th>
          <th>Role</th>
          <th>Department</th>
          <th>Status</th>
          <th>ID #</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
              <span class="muted"><?= htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td><?= htmlspecialchars((string) $u['role_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($u['dept_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <span class="pill <?= ((int) $u['is_active'] === 1) ? 'active' : 'inactive' ?>">
                <?= ((int) $u['is_active'] === 1) ? 'active' : 'inactive' ?>
              </span>
            </td>
            <td><?= htmlspecialchars((string) $u['employee_id'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <form method="post" action="staff_management.php" style="margin:0 0 0.35rem;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="reset_default_password">
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <button class="btn" type="submit">Reset to default (ID #)</button>
              </form>
              <details>
                <summary class="btn" style="display:inline-block;">Manage</summary>
                <form method="post" action="staff_management.php" style="margin-top:0.45rem;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="action" value="update_user">
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <label>Full name</label>
                  <input name="name" required maxlength="120" value="<?= htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8') ?>">
                  <label>Username</label>
                  <input name="username" required maxlength="64" value="<?= htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8') ?>">
                  <label>Employee ID</label>
                  <input name="employee_id" required maxlength="12" value="<?= htmlspecialchars((string) $u['employee_id'], ENT_QUOTES, 'UTF-8') ?>">
                  <label>Role</label>
                  <select name="role">
                    <?php foreach ($roles as $r): ?>
                      <?php $rn = (string) $r['name']; ?>
                      <option value="<?= htmlspecialchars($rn, ENT_QUOTES, 'UTF-8') ?>"<?= ((string) $u['role_name'] === $rn ? ' selected' : '') ?>><?= htmlspecialchars($rn, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label>Department</label>
                  <select name="dept_id">
                    <option value="0">— None —</option>
                    <?php foreach ($departments as $d): ?>
                      <?php $did = (int) $d['id']; ?>
                      <option value="<?= $did ?>"<?= ((int) ($u['dept_id'] ?? 0) === $did ? ' selected' : '') ?>><?= htmlspecialchars((string) $d['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label>Status</label>
                  <select name="is_active">
                    <option value="1"<?= ((int) $u['is_active'] === 1 ? ' selected' : '') ?>>active</option>
                    <option value="0"<?= ((int) $u['is_active'] === 0 ? ' selected' : '') ?>>inactive</option>
                  </select>
                  <button class="btn primary" type="submit" style="margin-top:0.45rem;">Save</button>
                </form>
              </details>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  $('#usersTable').DataTable({
    pageLength: 20,
    order: [[0, 'asc']]
  });
});
  </script>
    </main>
  </div>
</body>
</html>
