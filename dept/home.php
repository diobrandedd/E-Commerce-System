<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');
$user = ims_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Department Home — Pyx Tracker</title>
  <meta http-equiv="refresh" content="0; url=/rts/app/dist/dashboard">
  <style>
    body { font-family: system-ui, sans-serif; background:#fffdf6; color:#3f2f1e; padding: 2rem; }
    .card { background:#fff; border:1px solid #e8dcc5; border-radius:12px; padding:1.5rem; max-width: 600px; margin:auto; }
    a.btn { display:inline-block; background:#C8900A; color:white; padding:0.6rem 1rem; border-radius:8px; text-decoration:none; font-weight:600; }
    .muted { color:#7e6a4b; font-size:0.85rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.5rem;font-size:1.2rem;">Pyx Tracker has moved</h1>
    <p>The inventory module is now part of the new Pyx Tracker SPA. We're redirecting you in a moment.</p>
    <p><a class="btn" href="/rts/app/dist/dashboard">Open Pyx Tracker</a></p>
    <p class="muted">Signed in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p class="muted">Legacy pages still available:
      <a href="stock_entries.php">Stock entries</a> ·
      <a href="products.php">Products</a> ·
      <a href="../auth/logout.php">Logout</a>
    </p>
  </div>
</body>
</html>
