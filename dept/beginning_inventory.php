<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL], '../auth/login.php');

ims_session_start();
$user = ims_current_user();
$role = (string) ($user['role'] ?? '');
$userId = (int) ($user['id'] ?? 0);
$userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;

$canViewAll = ims_can_view_all_departments($role);
$canSet = ims_is_tl($role);

if (!$canViewAll && $userDeptId < 1) {
    http_response_code(400);
    echo 'Your account has no department assigned.';
    exit;
}

$notice = '';
$error = '';

if (!isset($_SESSION['ims_csrf']) || !is_string($_SESSION['ims_csrf']) || $_SESSION['ims_csrf'] === '') {
    $_SESSION['ims_csrf'] = bin2hex(random_bytes(24));
}
$csrf = (string) $_SESSION['ims_csrf'];

try {
    $pdo = ims_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database unavailable.';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedCsrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $error = 'Session expired. Refresh and try again.';
    } else {
        try {
            if (!$canSet) {
                throw new RuntimeException('Only TL can set beginning inventory.');
            }
            $deptId = $userDeptId;
            $stockId = (int) ($_POST['stock_id'] ?? 0);
            $qtyRaw = trim((string) ($_POST['qty'] ?? ''));
            $monthYear = trim((string) ($_POST['month_year'] ?? ''));

            if ($deptId < 1) {
                throw new RuntimeException('Department is missing in your account.');
            }
            if ($stockId < 1) {
                throw new RuntimeException('Stock is required.');
            }
            if (!is_numeric($qtyRaw) || (float) $qtyRaw < 0) {
                throw new RuntimeException('Qty must be 0 or greater.');
            }
            $qty = (float) $qtyRaw;
            if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
                throw new RuntimeException('Month must be YYYY-MM.');
            }

            $stStock = $pdo->prepare('SELECT id FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
            $stStock->execute([$stockId, $deptId]);
            if (!$stStock->fetch()) {
                throw new RuntimeException('Stock not found in your department.');
            }

            $upsert = $pdo->prepare(
                'INSERT INTO beginning_inventory (dept_id, stock_id, qty, set_by, month_year)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE qty = VALUES(qty), set_by = VALUES(set_by), created_at = CURRENT_TIMESTAMP'
            );
            $upsert->execute([$deptId, $stockId, $qty, $userId, $monthYear]);
            $notice = 'Beginning inventory saved.';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$departments = [];
$stocks = [];
$rows = [];

try {
    $stDept = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    $departments = $stDept ? $stDept->fetchAll() : [];

    $sqlStocks = 'SELECT s.id, s.name, s.dept_id, d.name AS dept_name
                  FROM stocks s
                  INNER JOIN departments d ON d.id = s.dept_id';
    $argsStocks = [];
    if (!$canViewAll) {
        $sqlStocks .= ' WHERE s.dept_id = ?';
        $argsStocks[] = $userDeptId;
    }
    $sqlStocks .= ' ORDER BY d.name ASC, s.name ASC';
    $stStocks = $pdo->prepare($sqlStocks);
    $stStocks->execute($argsStocks);
    $stocks = $stStocks->fetchAll();

    $sqlRows = 'SELECT bi.id, bi.month_year, bi.qty, bi.created_at,
                       d.name AS dept_name, s.name AS stock_name, u.name AS set_by_name
                FROM beginning_inventory bi
                INNER JOIN departments d ON d.id = bi.dept_id
                INNER JOIN stocks s ON s.id = bi.stock_id
                INNER JOIN users u ON u.id = bi.set_by';
    $argsRows = [];
    if (!$canViewAll) {
        $sqlRows .= ' WHERE bi.dept_id = ?';
        $argsRows[] = $userDeptId;
    }
    $sqlRows .= ' ORDER BY bi.month_year DESC, bi.created_at DESC, bi.id DESC';
    $stRows = $pdo->prepare($sqlRows);
    $stRows->execute($argsRows);
    $rows = $stRows->fetchAll();
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load beginning inventory data. Run sql/migration_phase3_inventory_products.sql.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Beginning Inventory</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); padding:1rem; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:0.95rem 1rem; margin-bottom:0.9rem; }
    .row { display:grid; gap:0.65rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    @media (max-width:980px){ .row { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px){ .row { grid-template-columns:1fr; } }
    label { display:block; font-size:0.78rem; color:var(--muted); margin:0.25rem 0 0.15rem; }
    input, select { width:100%; padding:0.5rem 0.55rem; border:1px solid var(--border); border-radius:8px; background:#fffdfa; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.45rem 0.7rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .muted { color:var(--muted); }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.2rem;font-size:1.25rem;">Beginning Inventory</h1>
    <p class="muted" style="margin:0 0 0.55rem;">
      Logged in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
      (<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>)
    </p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
      <a class="btn" href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role($role), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a>
      <a class="btn" href="products.php">Products & deductions</a>
      <a class="btn" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <?php if ($canSet): ?>
    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Set monthly beginning inventory (TL only)</h2>
      <form method="post" action="beginning_inventory.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="row">
          <div>
            <label for="month_year">Month</label>
            <input id="month_year" name="month_year" type="month" required>
          </div>
          <div>
            <label for="stock_id">Stock</label>
            <select id="stock_id" name="stock_id" required>
              <option value="">Select stock</option>
              <?php foreach ($stocks as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars((string) $s['name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="qty">Qty</label>
            <input id="qty" name="qty" type="number" min="0" step="0.01" required>
          </div>
          <div style="display:flex;align-items:flex-end;">
            <button class="btn primary" type="submit" style="width:100%;">Save beginning inventory</button>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="card">
    <table id="beginningTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Month</th>
          <th>Department</th>
          <th>Stock</th>
          <th>Qty</th>
          <th>Set by</th>
          <th>Saved at</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int) $r['id'] ?></td>
            <td><?= htmlspecialchars((string) $r['month_year'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $r['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $r['stock_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $r['qty'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $r['set_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  $('#beginningTable').DataTable({
    pageLength: 25,
    order: [[1, 'desc'], [0, 'desc']]
  });
});
  </script>
</body>
</html>
