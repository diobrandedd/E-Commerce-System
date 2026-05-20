<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

// One-time bootstrap lock: delete manually only if you intentionally need to rerun.
$lockFile = __DIR__ . '/../config/phase1_5_bootstrap.lock';

/**
 * Allow setup only from local machine for safety.
 */
function ims_setup_is_local_request(): bool
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip === '127.0.0.1' || $ip === '::1';
}

if (PHP_SAPI !== 'cli' && !ims_setup_is_local_request()) {
    http_response_code(403);
    echo 'Setup is allowed only from localhost.';
    exit;
}

$error = '';
$ok = '';

if (is_file($lockFile)) {
    $ok = 'Phase 1.5 bootstrap already completed. Lock file exists at config/phase1_5_bootstrap.lock.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !is_file($lockFile)) {
    $execName = trim((string) ($_POST['exec_name'] ?? ''));
    $execUsername = trim((string) ($_POST['exec_username'] ?? ''));
    $execEmployeeId = trim((string) ($_POST['exec_employee_id'] ?? ''));
    $execEmailSynth = '';
    $posLabel = trim((string) ($_POST['pos_label'] ?? 'Main POS'));
    $posPin = trim((string) ($_POST['pos_pin'] ?? ''));

    if ($execName === '') {
        $error = 'Executive name is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,64}$/', $execUsername)) {
        $error = 'Executive username must be 3-64 chars (letters, numbers, underscore, dash, dot).';
    } elseif (!preg_match('/^[0-9]{4,12}$/', $execEmployeeId)) {
        $error = 'Executive ID number must be 4-12 digits.';
    } elseif (!preg_match('/^[0-9]{4,12}$/', $posPin)) {
        $error = 'POS PIN must be 4-12 digits.';
    } else {
        $execEmailSynth = strtolower($execUsername) . '@pyx.local';
        try {
            $pdo = ims_pdo();
            $pdo->beginTransaction();

            $roleExec = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
            $roleExec->execute(['executive']);
            $execRoleId = (int) $roleExec->fetchColumn();
            if ($execRoleId < 1) {
                throw new RuntimeException('Role "executive" not found. Run sql/migration_phase1_inventory_auth.sql first.');
            }

            $execHash = password_hash($execEmployeeId, PASSWORD_BCRYPT);
            if ($execHash === false) {
                throw new RuntimeException('Could not hash executive password.');
            }
            $posHash = password_hash($posPin, PASSWORD_BCRYPT);
            if ($posHash === false) {
                throw new RuntimeException('Could not hash POS PIN.');
            }

            $stUser = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stUser->execute([$execUsername]);
            $existingUserId = (int) $stUser->fetchColumn();

            if ($existingUserId > 0) {
                $upd = $pdo->prepare(
                    'UPDATE users
                     SET name = ?, email = ?, employee_id = ?, password_hash = ?, role_id = ?, dept_id = NULL, is_active = 1
                     WHERE id = ?'
                );
                $upd->execute([$execName, $execEmailSynth, $execEmployeeId, $execHash, $execRoleId, $existingUserId]);
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO users (username, name, email, employee_id, password_hash, role_id, dept_id, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, NULL, 1)'
                );
                $ins->execute([$execUsername, $execName, $execEmailSynth, $execEmployeeId, $execHash, $execRoleId]);
            }

            $insPin = $pdo->prepare('INSERT INTO pos_pins (pin_hash, label, is_active) VALUES (?, ?, 1)');
            $insPin->execute([$posHash, $posLabel === '' ? 'Main POS' : $posLabel]);

            $pdo->commit();

            $stamp = 'Completed at ' . date('Y-m-d H:i:s') . PHP_EOL
                . 'Executive username: ' . $execUsername . PHP_EOL
                . 'POS label: ' . ($posLabel === '' ? 'Main POS' : $posLabel) . PHP_EOL;
            @file_put_contents($lockFile, $stamp);

            $ok = 'Phase 1.5 complete: executive user saved and POS PIN created. Setup is now locked.';
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Phase 1.5 Setup</title>
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); padding:1rem; }
    .card { width:100%; max-width:560px; border:1px solid var(--border); border-radius:12px; background:var(--panel); padding:1rem 1.1rem; }
    .msg-ok { color:var(--ok); margin:0 0 0.6rem; font-size:0.92rem; }
    .msg-err { color:var(--danger); margin:0 0 0.6rem; font-size:0.92rem; }
    .muted { color:var(--muted); font-size:0.86rem; }
    label { display:block; margin:0.65rem 0 0.2rem; font-size:0.82rem; color:var(--muted); }
    input { width:100%; border:1px solid var(--border); border-radius:8px; padding:0.55rem 0.62rem; background:#fffdfa; }
    button { margin-top:0.95rem; width:100%; border:none; border-radius:10px; background:var(--accent); color:#111; padding:0.62rem; font-weight:700; cursor:pointer; }
    button:disabled { opacity:0.55; cursor:not-allowed; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.3rem;font-size:1.2rem;">Phase 1.5 Bootstrap</h1>
    <p class="muted" style="margin:0 0 0.7rem;">Creates/updates first inventory executive user and creates one POS PIN using bcrypt hashes. Executive default password is the ID number.</p>

    <?php if ($ok !== ''): ?><p class="msg-ok"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="msg-err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <form method="post" action="phase1_5_setup.php">
      <label for="exec_name">Executive full name</label>
      <input id="exec_name" name="exec_name" required value="Executive User">

      <label for="exec_username">Executive username (login)</label>
      <input id="exec_username" name="exec_username" required value="Executive_1">

      <label for="exec_employee_id">Executive ID number (default password)</label>
      <input id="exec_employee_id" name="exec_employee_id" required inputmode="numeric" pattern="[0-9]{4,12}" value="000001">

      <label for="pos_label">POS label</label>
      <input id="pos_label" name="pos_label" value="Main POS">

      <label for="pos_pin">POS PIN (4-12 digits)</label>
      <input id="pos_pin" name="pos_pin" type="password" inputmode="numeric" pattern="[0-9]{4,12}" required>

      <button type="submit" <?= is_file($lockFile) ? 'disabled' : '' ?>>Run Phase 1.5 Setup</button>
    </form>
  </div>
</body>
</html>
