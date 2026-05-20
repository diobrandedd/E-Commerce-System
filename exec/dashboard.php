<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
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
</head>
<body>
  <h1>Executive Dashboard</h1>
  <p>Welcome, <?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.</p>
  <p>Phase 2 modules are active.</p>
  <ul>
    <li><a href="staff_management.php">Staff management</a></li>
    <li><a href="../dept/stock_entries.php">Stock entries (all departments)</a></li>
    <li><a href="../dept/products.php">Products, formulas, deductions</a></li>
    <li><a href="../dept/beginning_inventory.php">Beginning inventory (view all)</a></li>
    <li>
      <a href="edit_requests.php">Edit requests review</a>
      <?php if ($pendingCount > 0): ?>
        <strong style="color:#8f2e2e;">(<?= $pendingCount ?> pending)</strong>
      <?php endif; ?>
    </li>
    <li><a href="../auth/settings.php">Settings</a></li>
    <li><a href="../auth/logout.php">Logout</a></li>
  </ul>
</body>
</html>
