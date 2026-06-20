<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_PROD_MANAGER], '../auth/login.php');
$user = ims_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Production Manager Dashboard</title>
</head>
<body>
  <h1>Production Manager Dashboard</h1>
  <p>Welcome, <?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.</p>
  <ul>
    <li><a href="../dept/stock_entries.php">Stock entries (all departments)</a></li>
    <li><a href="../dept/products.php">Products, formulas, deductions</a></li>
    <li><a href="../dept/beginning_inventory.php">Beginning inventory (view all)</a></li>
    <li><a href="../auth/settings.php">Settings</a></li>
    <li><a href="../auth/logout.php">Logout</a></li>
  </ul>
</body>
</html>
