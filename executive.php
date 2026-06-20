<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

rts_require_roles([RTS_ROLE_EXECUTIVE], 'login.php');
rts_session_start();
$auth = rts_auth_user();

$roleLabels = rts_user_role_labels();
$statusLabels = rts_user_status_labels();
$deptLabels = rts_department_labels();

/** @return list<string> */
function rts_manage_role_values(): array
{
    return [
        RTS_ROLE_EXECUTIVE,
        RTS_ROLE_PRODUCTION_MANAGER,
        RTS_ROLE_TEAM_LEADER,
        RTS_ROLE_STAFF,
    ];
}

function rts_username_valid(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.-]{3,64}$/', $username);
}

function rts_employee_id_valid(string $employeeId): bool
{
    return (bool) preg_match('/^[0-9]{4,12}$/', $employeeId);
}

function rts_staff_needs_department(string $role): bool
{
    return $role === RTS_ROLE_TEAM_LEADER || $role === RTS_ROLE_STAFF;
}

$notice = '';
$error = '';

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><p>Database connection failed. Check <code>config/config.php</code>.</p></body></html>';
    exit;
}

if (!isset($_SESSION['rts_exec_csrf']) || !is_string($_SESSION['rts_exec_csrf']) || $_SESSION['rts_exec_csrf'] === '') {
    $_SESSION['rts_exec_csrf'] = bin2hex(random_bytes(24));
}
$csrf = (string) $_SESSION['rts_exec_csrf'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedCsrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $error = 'Session expired. Refresh and try again.';
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));
        try {
            if ($action === 'create_staff') {
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $employeeId = trim((string) ($_POST['employee_id'] ?? ''));
                $role = trim((string) ($_POST['role'] ?? ''));
                $status = trim((string) ($_POST['status'] ?? RTS_USER_STATUS_ACTIVE));
                $department = trim((string) ($_POST['department'] ?? ''));

                if ($fullName === '') {
                    throw new RuntimeException('Full name is required.');
                }
                if (!rts_username_valid($username)) {
                    throw new RuntimeException('Username must be 3-64 chars: letters, numbers, underscore, dash, dot.');
                }
                if (!rts_employee_id_valid($employeeId)) {
                    throw new RuntimeException('ID # must be 4-12 digits.');
                }
                if (!in_array($role, rts_manage_role_values(), true)) {
                    throw new RuntimeException('Invalid role.');
                }
                if (!rts_status_valid($status)) {
                    throw new RuntimeException('Invalid status.');
                }
                if (rts_staff_needs_department($role)) {
                    if (!rts_department_valid($department)) {
                        throw new RuntimeException('Team Leader / Staff must have a department.');
                    }
                } else {
                    $department = '';
                }

                $hash = password_hash($employeeId, PASSWORD_BCRYPT);
                $ins = $pdo->prepare(
                    'INSERT INTO app_users (username, full_name, employee_id, password_hash, role, department, status, created_by_user_id)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $ins->execute([
                    $username,
                    $fullName,
                    $employeeId,
                    $hash,
                    $role,
                    $department === '' ? null : $department,
                    $status,
                    (int) ($auth['id'] ?? 0),
                ]);
                $notice = 'Staff account created. Initial password is the ID #.';
            } elseif ($action === 'update_staff') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                if ($userId < 1) {
                    throw new RuntimeException('Invalid user.');
                }
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $employeeId = trim((string) ($_POST['employee_id'] ?? ''));
                $role = trim((string) ($_POST['role'] ?? ''));
                $status = trim((string) ($_POST['status'] ?? RTS_USER_STATUS_ACTIVE));
                $department = trim((string) ($_POST['department'] ?? ''));

                if ($fullName === '') {
                    throw new RuntimeException('Full name is required.');
                }
                if (!rts_username_valid($username)) {
                    throw new RuntimeException('Username must be 3-64 chars: letters, numbers, underscore, dash, dot.');
                }
                if (!rts_employee_id_valid($employeeId)) {
                    throw new RuntimeException('ID # must be 4-12 digits.');
                }
                if (!in_array($role, rts_manage_role_values(), true)) {
                    throw new RuntimeException('Invalid role.');
                }
                if (!rts_status_valid($status)) {
                    throw new RuntimeException('Invalid status.');
                }
                if (rts_staff_needs_department($role)) {
                    if (!rts_department_valid($department)) {
                        throw new RuntimeException('Team Leader / Staff must have a department.');
                    }
                } else {
                    $department = '';
                }

                $upd = $pdo->prepare(
                    'UPDATE app_users
                     SET username = ?, full_name = ?, employee_id = ?, role = ?, department = ?, status = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $username,
                    $fullName,
                    $employeeId,
                    $role,
                    $department === '' ? null : $department,
                    $status,
                    $userId,
                ]);
                $notice = 'Staff account updated.';
            } elseif ($action === 'reset_password') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                if ($userId < 1) {
                    throw new RuntimeException('Invalid user.');
                }
                $st = $pdo->prepare('SELECT employee_id FROM app_users WHERE id = ? LIMIT 1');
                $st->execute([$userId]);
                $row = $st->fetch();
                if (!$row) {
                    throw new RuntimeException('User not found.');
                }
                $employeeId = trim((string) ($row['employee_id'] ?? ''));
                if (!rts_employee_id_valid($employeeId)) {
                    throw new RuntimeException('Cannot reset password: invalid ID #.');
                }
                $hash = password_hash($employeeId, PASSWORD_BCRYPT);
                $pdo->prepare('UPDATE app_users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
                $notice = 'Password reset to ID # for selected user.';
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $error = 'Username or ID # already exists.';
            } else {
                $error = 'Could not save. Run sql/migration_app_users_hierarchy.sql first.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$users = [];
try {
    $st = $pdo->query('SELECT id, username, full_name, employee_id, role, department, status, created_at FROM app_users ORDER BY created_at DESC, id DESC');
    $users = $st ? $st->fetchAll() : [];
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load users. Run sql/migration_app_users_hierarchy.sql first.';
}

$pageTitle = 'Executive settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    :root { --bg:#fffdf6; --panel:#ffffff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); line-height:1.45; display:flex; min-height:100vh; }
    .sidebar { width:230px; padding:1.25rem 1rem; border-right:1px solid var(--border); background:#fff5df; display:flex; flex-direction:column; gap:1rem; position:sticky; top:0; align-self:flex-start; height:100vh; overflow:auto; }
    .sidebar h1 { margin:0; font-size:1.08rem; }
    .nav-links a { display:block; padding:0.4rem 0.1rem; color:var(--muted); text-decoration:none; font-size:0.92rem; }
    .nav-links a:hover, .nav-links a.active { color:var(--accent); font-weight:600; }
    main { flex:1; max-width:1200px; margin:0 auto; padding:1.25rem; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:0.95rem 1rem; margin-bottom:0.9rem; }
    .title { margin:0 0 0.2rem; font-size:1.2rem; }
    .muted { color:var(--muted); }
    .row { display:grid; gap:0.65rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    @media (max-width:980px){ .row { grid-template-columns:repeat(2,minmax(0,1fr)); } .sidebar { display:none; } main { padding:0.75rem; } }
    @media (max-width:640px){ .row { grid-template-columns:1fr; } }
    label { display:block; font-size:0.78rem; color:var(--muted); margin:0.25rem 0 0.15rem; }
    input, select { width:100%; padding:0.48rem 0.55rem; border:1px solid var(--border); border-radius:8px; background:#fffdfa; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.5rem 0.75rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .pill { display:inline-flex; align-items:center; font-size:0.75rem; border:1px solid var(--border); border-radius:999px; padding:0.14rem 0.48rem; background:#fff9ef; }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
    .table-wrap { overflow:auto; }
    table { width:100%; border-collapse:collapse; min-width:980px; }
    th, td { border-bottom:1px solid var(--border); vertical-align:top; text-align:left; padding:0.55rem 0.45rem; font-size:0.88rem; }
    th { color:var(--muted); font-size:0.78rem; }
    div.dataTables_wrapper div.dataTables_filter input,
    div.dataTables_wrapper div.dataTables_length select {
      border:1px solid var(--border);
      border-radius:8px;
      padding:0.35rem 0.45rem;
      background:#fffdfa;
      color:var(--text);
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { font-size:0.85rem; color:var(--muted); }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div>
      <h1>Executive panel</h1>
      <p class="muted" style="margin:0.22rem 0 0;font-size:0.84rem;"><?= htmlspecialchars((string) ($auth['full_name'] ?? $auth['username'] ?? 'Executive'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <nav class="nav-links">
      <a href="#settings" class="active">Settings</a>
      <a href="#staff-management">Staff management</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="deliveries/index.php">Deliveries upload</a>
      <a href="record.php">Record RTS</a>
      <a href="records.php">Records</a>
      <a href="settings.php">Settings</a>
      <a href="logout.php">Sign out</a>
    </nav>
  </aside>

  <main>
    <section id="settings" class="card">
      <h2 class="title">Settings</h2>
      <p class="muted" style="margin:0;">Executive tools. Use Staff Management below to create users, assign role/department, and set account status.</p>
    </section>

    <section id="staff-management" class="card">
      <h2 class="title">Staff management</h2>
      <p class="muted" style="margin:0 0 0.6rem;">Username is login handle (example: <code>Noli_F</code>). Full name is display name (example: <code>Noli M. Fin</code>). Password is set/reset to the ID #.</p>
      <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

      <form method="post" action="executive.php" style="margin-bottom:0.75rem;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create_staff">
        <div class="row">
          <div>
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" required maxlength="120" placeholder="Noli M. Fin">
          </div>
          <div>
            <label for="username">Username (login)</label>
            <input id="username" name="username" required maxlength="64" placeholder="Noli_F">
          </div>
          <div>
            <label for="employee_id">ID # (also initial password)</label>
            <input id="employee_id" name="employee_id" required maxlength="12" inputmode="numeric" placeholder="025027">
          </div>
          <div>
            <label for="role">Role</label>
            <select id="role" name="role" required>
              <?php foreach (rts_manage_role_values() as $rv): ?>
              <option value="<?= htmlspecialchars($rv, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($roleLabels[$rv] ?? $rv, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="department">Department</label>
            <select id="department" name="department">
              <option value="">— Not assigned —</option>
              <?php foreach ($deptLabels as $dv => $dl): ?>
              <option value="<?= htmlspecialchars($dv, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dl, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="status">Status</label>
            <select id="status" name="status">
              <?php foreach ($statusLabels as $sv => $sl): ?>
              <option value="<?= htmlspecialchars($sv, ENT_QUOTES, 'UTF-8') ?>"<?= $sv === RTS_USER_STATUS_ACTIVE ? ' selected' : '' ?>><?= htmlspecialchars($sl, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;align-items:flex-end;">
            <button type="submit" class="btn primary" style="width:100%;">Add staff</button>
          </div>
        </div>
      </form>

      <div class="table-wrap">
        <table id="staffTable">
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
            <?php if ($users === []): ?>
            <tr><td colspan="6"><em class="muted">No users found.</em></td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars((string) ($u['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
                <span class="muted"><?= htmlspecialchars((string) ($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td><?= htmlspecialchars($roleLabels[(string) ($u['role'] ?? '')] ?? (string) ($u['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($deptLabels[(string) ($u['department'] ?? '')] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="pill"><?= htmlspecialchars($statusLabels[(string) ($u['status'] ?? '')] ?? (string) ($u['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars((string) ($u['employee_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <form method="post" action="executive.php" style="margin:0 0 0.35rem;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="action" value="reset_password">
                  <input type="hidden" name="user_id" value="<?= (int) ($u['id'] ?? 0) ?>">
                  <button class="btn" type="submit">Reset to default (ID #)</button>
                </form>
                <details>
                  <summary class="btn" style="display:inline-block;">Manage</summary>
                  <form method="post" action="executive.php" style="margin-top:0.45rem;">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="update_staff">
                    <input type="hidden" name="user_id" value="<?= (int) ($u['id'] ?? 0) ?>">
                    <label>Full name</label>
                    <input name="full_name" required maxlength="120" value="<?= htmlspecialchars((string) ($u['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <label>Username</label>
                    <input name="username" required maxlength="64" value="<?= htmlspecialchars((string) ($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <label>ID #</label>
                    <input name="employee_id" required maxlength="12" value="<?= htmlspecialchars((string) ($u['employee_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <label>Role</label>
                    <select name="role">
                      <?php foreach (rts_manage_role_values() as $rv): ?>
                      <option value="<?= htmlspecialchars($rv, ENT_QUOTES, 'UTF-8') ?>"<?= ((string) ($u['role'] ?? '') === $rv ? ' selected' : '') ?>><?= htmlspecialchars($roleLabels[$rv] ?? $rv, ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                    <label>Department</label>
                    <select name="department">
                      <option value="">— Not assigned —</option>
                      <?php foreach ($deptLabels as $dv => $dl): ?>
                      <option value="<?= htmlspecialchars($dv, ENT_QUOTES, 'UTF-8') ?>"<?= ((string) ($u['department'] ?? '') === $dv ? ' selected' : '') ?>><?= htmlspecialchars($dl, ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                    <label>Status</label>
                    <select name="status">
                      <?php foreach ($statusLabels as $sv => $sl): ?>
                      <option value="<?= htmlspecialchars($sv, ENT_QUOTES, 'UTF-8') ?>"<?= ((string) ($u['status'] ?? '') === $sv ? ' selected' : '') ?>><?= htmlspecialchars($sl, ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div style="margin-top:0.5rem; display:flex; gap:0.35rem; flex-wrap:wrap;">
                      <button class="btn primary" type="submit">Save</button>
                    </div>
                  </form>
                </details>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  $('#staffTable').DataTable({
    pageLength: 15,
    lengthMenu: [10, 15, 25, 50, 100],
    order: [],
    autoWidth: false
  });
});
  </script>
</body>
</html>
