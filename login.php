<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (rts_auth_user() !== null) {
    header('Location: ' . rts_login_redirect_by_role((string) rts_auth_user()['role']), true, 302);
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if (rts_auth_login($user, $pass, '')) {
        $auth = rts_auth_user();
        $role = $auth !== null ? (string) $auth['role'] : '';
        header('Location: ' . rts_login_redirect_by_role($role), true, 302);
        exit;
    }
    $error = 'Invalid username/password, or account is not active.';
}

$pageTitle = 'Pyx Tracker Management System — Sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); display:flex; align-items:center; justify-content:center; padding:1rem; opacity:1; transition:opacity .22s ease; }
    body.page-enter { animation:pageIn .28s ease both; }
    body.page-exit { opacity:0; }
    @keyframes pageIn { from { opacity:0; } to { opacity:1; } }
    .wrap { width:100%; max-width:420px; }
    .brand { display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem; }
    .brand img { height:48px; width:auto; border-radius:8px; }
    h1 { margin:0; font-size:1.35rem; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.35rem; box-shadow:0 12px 32px rgba(101,76,36,0.1); }
    label { display:block; font-size:0.8rem; color:var(--muted); margin:0.75rem 0 0.25rem; }
    input[type="text"], input[type="password"] { width:100%; padding:0.55rem 0.65rem; border-radius:8px; border:1px solid var(--border); font-size:1rem; background:#fffdfa; }
    .btn { display:inline-flex; align-items:center; justify-content:center; width:100%; padding:0.65rem 1rem; border-radius:10px; border:none; background:var(--accent); color:#061018; font-weight:700; font-size:1rem; cursor:pointer; margin-top:1rem; }
    .btn:hover { filter:brightness(1.05); }
    .err { color:var(--danger); font-size:0.9rem; margin-top:0.75rem; }
    .delivery-cta {
      margin-top:1.35rem;
      padding:1rem 1rem 1.1rem;
      border-radius:12px;
      border:2px solid var(--accent);
      background: linear-gradient(180deg, #fff9e8 0%, #fff5df 100%);
      text-align:center;
    }
    .delivery-cta p { margin:0 0 0.65rem; font-size:0.92rem; color:var(--text); font-weight:600; }
    .delivery-cta a {
      display:inline-flex; align-items:center; justify-content:center; width:100%;
      padding:0.75rem 1rem; border-radius:10px; background:#2d6a4f; color:#fff; font-weight:700;
      text-decoration:none; font-size:1.05rem; box-shadow:0 4px 14px rgba(45,106,79,0.35);
    }
    .delivery-cta a:hover { filter:brightness(1.06); }
    .delivery-cta small { display:block; margin-top:0.5rem; color:var(--muted); font-size:0.82rem; }
  </style>
</head>
<body class="page-enter">
  <div class="wrap">
    <div class="brand">
      <img src="assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" onerror="this.style.display='none'">
      <h1>Pyx Tracker Management System</h1>
    </div>
    <div class="card">
      <p style="margin:0 0 0.5rem;color:var(--muted);font-size:0.92rem;">Sign in with your assigned Pyx Tracker account.</p>
      <?php if ($error !== ''): ?>
        <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <form method="post" action="login.php" autocomplete="on">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username" autofocus>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="submit" class="btn">Sign in</button>
      </form>
    </div>

    <div class="delivery-cta">
      <p>Deliveries</p>
      <a href="deliveries/index.php" id="deliveryBulkLink">Go to deliveries</a>
      <small>Sign in above, or open deliveries if you are already signed in.</small>
    </div>
  </div>
  <script>
(function () {
  var a = document.getElementById('deliveryBulkLink');
  if (!a) return;
  a.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var href = a.getAttribute('href');
    if (!href) return;
    e.preventDefault();
    document.body.classList.add('page-exit');
    setTimeout(function () { window.location.href = href; }, 180);
  });
})();
  </script>
</body>
</html>
