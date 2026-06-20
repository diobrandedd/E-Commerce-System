<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

ims_session_start();
if (ims_pos_authenticated()) {
    header('Location: ../' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);
    exit;
}
$existing = ims_current_user();
if ($existing !== null) {
    header('Location: ../' . ims_redirect_dashboard_by_role($existing['role']), true, 302);
    exit;
}

$error = '';
$mode = 'user';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $mode = trim((string) ($_POST['mode'] ?? 'user'));
    if ($mode === 'pos') {
        $pin = (string) ($_POST['pin'] ?? '');
        if (ims_login_pos($pin)) {
            header('Location: ../' . ims_redirect_dashboard_by_role(IMS_ROLE_POS), true, 302);
            exit;
        }
        $error = 'Invalid POS PIN or inactive POS account.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (ims_login_user($username, $password)) {
            $u = ims_current_user();
            $role = $u !== null ? $u['role'] : '';
            header('Location: ../' . ims_redirect_dashboard_by_role($role), true, 302);
            exit;
        }
        $error = 'Invalid credentials or inactive account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pyx Tracker Inventory - Login</title>
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; }
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg); color:var(--text); font-family:system-ui,sans-serif; padding:1rem; }
    .wrap { width:100%; max-width:460px; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.1rem 1.2rem; box-shadow:0 10px 28px rgba(101,76,36,0.12); }
    .tabs { display:flex; gap:0.4rem; margin-bottom:0.8rem; }
    .tabs button { flex:1; border:1px solid var(--border); border-radius:9px; padding:0.5rem; background:#fff9ef; font-weight:600; cursor:pointer; }
    .tabs button.active { border-color:var(--accent); color:var(--accent); }
    .hidden { display:none; }
    label { display:block; font-size:0.8rem; color:var(--muted); margin:0.7rem 0 0.2rem; }
    input { width:100%; border:1px solid var(--border); border-radius:8px; background:#fffdfa; padding:0.56rem 0.62rem; font-size:1rem; }
    .btn { width:100%; border:none; border-radius:10px; padding:0.64rem 0.9rem; margin-top:0.95rem; background:var(--accent); color:#111; font-weight:700; cursor:pointer; }
    .err { margin:0 0 0.6rem; color:var(--danger); font-size:0.9rem; }
    .hint { color:var(--muted); font-size:0.83rem; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1 style="margin:0 0 0.2rem;font-size:1.25rem;">Pyx Tracker Inventory</h1>
      <p class="hint" style="margin:0 0 0.65rem;">Phase 1 authentication (regular users + POS PIN session).</p>
      <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

      <div class="tabs">
        <button type="button" id="tabUser" class="<?= $mode !== 'pos' ? 'active' : '' ?>">User login</button>
        <button type="button" id="tabPos" class="<?= $mode === 'pos' ? 'active' : '' ?>">POS PIN</button>
      </div>

      <form method="post" action="login.php" id="formUser" class="<?= $mode === 'pos' ? 'hidden' : '' ?>">
        <input type="hidden" name="mode" value="user">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autocomplete="username">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <p class="hint" style="margin:0.45rem 0 0;">Default password is the employee ID number.</p>
        <button class="btn" type="submit">Sign in</button>
      </form>

      <form method="post" action="login.php" id="formPos" class="<?= $mode === 'pos' ? '' : 'hidden' ?>">
        <input type="hidden" name="mode" value="pos">
        <label for="pin">POS PIN</label>
        <input id="pin" name="pin" type="password" inputmode="numeric" required autocomplete="one-time-code">
        <button class="btn" type="submit">Open POS</button>
      </form>
    </div>
  </div>
  <script>
(function () {
  var tabUser = document.getElementById('tabUser');
  var tabPos = document.getElementById('tabPos');
  var formUser = document.getElementById('formUser');
  var formPos = document.getElementById('formPos');
  if (!tabUser || !tabPos || !formUser || !formPos) return;
  function setMode(pos) {
    tabUser.classList.toggle('active', !pos);
    tabPos.classList.toggle('active', pos);
    formUser.classList.toggle('hidden', pos);
    formPos.classList.toggle('hidden', !pos);
  }
  tabUser.addEventListener('click', function () { setMode(false); });
  tabPos.addEventListener('click', function () { setMode(true); });
})();
  </script>
</body>
</html>
