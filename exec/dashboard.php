<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/ims_exec_sidebar.php';
requireRole([IMS_ROLE_EXECUTIVE], '../auth/login.php');
$user = ims_current_user();

$pendingCount = 0;
try {
    $pdo = ims_pdo();
    $st = $pdo->query("SELECT COUNT(*) FROM edit_requests WHERE status = 'pending'");
    $pendingCount = $st ? (int) $st->fetchColumn() : 0;
} catch (Throwable $e) {
    $pendingCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Executive Dashboard</title>
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; }
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
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:1rem; margin-bottom:0.85rem; }
    .muted { color:var(--muted); }
    @media (max-width:960px) {
      .layout { grid-template-columns:1fr; }
      .exec-sidebar { border-right:0; border-bottom:1px solid var(--border); }
    }
  </style>
</head>
<body>
  <div class="layout">
    <?php ims_render_exec_sidebar('dashboard'); ?>
    <main class="content">
      <div class="card">
        <h1 style="margin:0 0 0.2rem;font-size:1.3rem;">Executive Dashboard</h1>
        <p class="muted" style="margin:0;">Welcome, <?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.</p>
      </div>
      <div class="card">
        <p style="margin:0 0 0.45rem;">Quick status</p>
        <p class="muted" style="margin:0;">
          Pending edit requests:
          <strong><?= $pendingCount ?></strong>
        </p>
      </div>
    </main>
  </div>
</body>
</html>
