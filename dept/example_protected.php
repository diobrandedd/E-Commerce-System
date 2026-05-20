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
  <title>Example Protected Page</title>
</head>
<body>
  <h1>Example Protected Page</h1>
  <p>This page is accessible only to TL/Staff roles through <code>requireRole()</code>.</p>
  <p>Current session role: <?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p><a href="home.php">Back to dept home</a></p>
</body>
</html>
