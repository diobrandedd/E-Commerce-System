<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_POS], '../auth/login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>POS</title>
</head>
<body>
  <h1>POS Session</h1>
  <p>POS is authenticated using <code>$_SESSION['pos_authenticated']</code> only.</p>
  <p><a href="../auth/logout.php">Exit POS</a></p>
</body>
</html>
