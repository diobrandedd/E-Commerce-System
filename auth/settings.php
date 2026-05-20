<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL, IMS_ROLE_STAFF], 'login.php');

$u = ims_current_user();
$notice = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $next = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (!hash_equals($next, $confirm)) {
        $error = 'New password and confirmation do not match.';
    } else {
        $res = ims_change_password((int) ($u['id'] ?? 0), $current, $next);
        if (!empty($res['ok'])) {
            $notice = 'Password changed successfully.';
        } else {
            $error = (string) ($res['error'] ?? 'Could not change password.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inventory Settings</title>
</head>
<body>
  <h1>Inventory User Settings</h1>
  <p>User: <strong><?= htmlspecialchars((string) ($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
  <?php if ($error !== ''): ?><p style="color:#c65a4a;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($notice !== ''): ?><p style="color:#2d6a4f;"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <form method="post" action="settings.php">
    <label>Current password</label><br>
    <input type="password" name="current_password" required><br><br>
    <label>New password</label><br>
    <input type="password" name="new_password" required minlength="6"><br><br>
    <label>Confirm new password</label><br>
    <input type="password" name="confirm_password" required minlength="6"><br><br>
    <button type="submit">Change password</button>
  </form>
  <p><a href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role((string) ($u['role'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a></p>
</body>
</html>
