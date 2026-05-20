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
  <title>Department Home</title>
</head>
<body>
  <h1>Department Home</h1>
  <p>User: <?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p>Role: <?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p>dept_id from session: <?= isset($user['dept_id']) ? (int) $user['dept_id'] : 0 ?></p>
  <ul>
    <li><a href="stock_entries.php">Stock entries</a></li>
    <li><a href="products.php">Products, formulas, deductions</a></li>
    <?php if ((string) ($user['role'] ?? '') === IMS_ROLE_TL): ?>
      <li><a href="beginning_inventory.php">Set beginning inventory</a></li>
    <?php endif; ?>
    <li><a href="example_protected.php">Example protected page</a></li>
    <li><a href="../auth/settings.php">Settings</a></li>
    <li><a href="../auth/logout.php">Logout</a></li>
  </ul>
</body>
</html>
