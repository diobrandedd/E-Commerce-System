<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
rts_require_roles([RTS_ROLE_TEAM_LEADER, RTS_ROLE_STAFF], 'login.php');

$u = rts_auth_user();
$labelsRole = rts_user_role_labels();
$labelsDept = rts_department_labels();

$role = $u !== null ? (string) ($u['role'] ?? '') : '';
$department = $u !== null ? (string) ($u['department'] ?? '') : '';

$pageTitle = 'Pyx Tracker portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
    .card { width:100%; max-width:560px; background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.1rem 1.2rem; }
    .meta { color:var(--muted); font-size:0.92rem; margin:0 0 0.5rem; }
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:0.55rem 0.85rem; border-radius:9px; border:1px solid var(--border); color:var(--text); text-decoration:none; font-weight:600; background:#fff9ef; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.45rem;">Welcome, <?= htmlspecialchars((string) ($u['full_name'] ?? $u['username'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="meta">Role: <?= htmlspecialchars($labelsRole[$role] ?? $role, ENT_QUOTES, 'UTF-8') ?></p>
    <p class="meta">Department: <?= htmlspecialchars($department !== '' ? ($labelsDept[$department] ?? $department) : 'Not assigned', ENT_QUOTES, 'UTF-8') ?></p>
    <p style="margin:0.6rem 0 1rem;">Your restricted portal is now enabled. Department-specific pages will be added next.</p>
    <div style="display:flex;gap:0.45rem;flex-wrap:wrap;">
      <a class="btn" href="deliveries/index.php">Deliveries upload</a>
      <a class="btn" href="settings.php">Settings</a>
      <a class="btn" href="logout.php">Sign out</a>
    </div>
  </div>
</body>
</html>
