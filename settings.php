<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

rts_require_roles([
    RTS_ROLE_EXECUTIVE,
    RTS_ROLE_PRODUCTION_MANAGER,
    RTS_ROLE_TEAM_LEADER,
    RTS_ROLE_STAFF,
    RTS_ROLE_DELIVERY,
    RTS_ROLE_ADMIN,
], 'login.php');

$user = rts_auth_user();
$notice = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $next = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($current === '' || $next === '' || $confirm === '') {
        $error = 'All password fields are required.';
    } elseif (strlen($next) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif (!hash_equals($next, $confirm)) {
        $error = 'New password and confirmation do not match.';
    } else {
        try {
            $pdo = rts_pdo();
            $st = $pdo->prepare('SELECT password_hash FROM app_users WHERE id = ? LIMIT 1');
            $st->execute([(int) ($user['id'] ?? 0)]);
            $row = $st->fetch();
            if (!$row) {
                throw new RuntimeException('User record not found.');
            }
            if (!password_verify($current, (string) $row['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($next, PASSWORD_BCRYPT);
                if ($hash === false) {
                    throw new RuntimeException('Could not hash new password.');
                }
                $upd = $pdo->prepare('UPDATE app_users SET password_hash = ? WHERE id = ?');
                $upd->execute([$hash, (int) ($user['id'] ?? 0)]);
                $notice = 'Password updated successfully.';
            }
        } catch (Throwable $e) {
            $error = $error !== '' ? $error : 'Could not change password right now.';
        }
    }
}

$pageTitle = 'User settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; background:var(--bg); color:var(--text); font-family:system-ui,sans-serif; display:flex; align-items:center; justify-content:center; padding:1rem; }
    .card { width:100%; max-width:520px; border:1px solid var(--border); border-radius:12px; background:var(--panel); padding:1rem 1.1rem; }
    .muted { color:var(--muted); font-size:0.88rem; }
    .err { color:var(--danger); font-size:0.9rem; margin:0 0 0.6rem; }
    .ok { color:var(--ok); font-size:0.9rem; margin:0 0 0.6rem; }
    label { display:block; color:var(--muted); font-size:0.8rem; margin:0.7rem 0 0.2rem; }
    input { width:100%; border:1px solid var(--border); border-radius:8px; padding:0.56rem 0.62rem; background:#fffdfa; }
    .btn { margin-top:0.95rem; width:100%; border:none; border-radius:10px; background:var(--accent); color:#111; padding:0.62rem; font-weight:700; cursor:pointer; }
    .link { display:inline-block; margin-top:0.6rem; color:var(--muted); text-decoration:none; font-size:0.85rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.25rem;font-size:1.18rem;">Settings</h1>
    <p class="muted" style="margin:0 0 0.55rem;">User: <strong><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <form method="post" action="settings.php">
      <label for="current_password">Current password</label>
      <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
      <label for="new_password">New password</label>
      <input id="new_password" name="new_password" type="password" required minlength="6" autocomplete="new-password">
      <label for="confirm_password">Confirm new password</label>
      <input id="confirm_password" name="confirm_password" type="password" required minlength="6" autocomplete="new-password">
      <button class="btn" type="submit">Change password</button>
    </form>

    <a class="link" href="index.php">Back</a>
  </div>
</body>
</html>
