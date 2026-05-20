<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');

ims_session_start();
$user = ims_current_user();
$role = (string) ($user['role'] ?? '');
$userId = (int) ($user['id'] ?? 0);
$userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;

$canViewAll = ims_can_view_all_departments($role);
$canAddStockEntries = ims_is_executive($role);
$canUseSupplierBatch = ims_is_executive($role);
$canUseSupplierIdentityAndPrice = ims_is_executive($role);
$canRequestEdits = ims_is_tl($role) || ims_is_staff($role);

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
        $action = trim((string) ($_POST['action'] ?? ''));
        try {
            if ($action === 'add_entry') {
                if (!$canAddStockEntries) {
                    throw new RuntimeException('Only Executive can add stock entries.');
                }
                $stockName = trim((string) ($_POST['stock_name'] ?? ''));
                $unit = trim((string) ($_POST['unit'] ?? 'pcs'));
                $qty = (float) ($_POST['qty'] ?? 0);
                $batchingDateRaw = trim((string) ($_POST['batching_date'] ?? ''));
                $supplierBatchNo = trim((string) ($_POST['supplier_batch_no'] ?? ''));
                $supplierName = trim((string) ($_POST['supplier_name'] ?? ''));
                $supplierCode = trim((string) ($_POST['supplier_code'] ?? ''));
                $currentPrice = trim((string) ($_POST['current_price'] ?? ''));

                $deptId = $canViewAll
                    ? (int) ($_POST['dept_id'] ?? 0)
                    : $userDeptId;

                if ($deptId < 1) {
                    throw new RuntimeException('Department is required.');
                }
                if ($stockName === '') {
                    throw new RuntimeException('Stock name is required.');
                }
                if ($qty <= 0) {
                    throw new RuntimeException('Quantity must be greater than zero.');
                }
                if ($batchingDateRaw === '') {
                    throw new RuntimeException('Batching date is required.');
                }
                $batchingDate = str_replace('T', ' ', $batchingDateRaw);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $batchingDate)) {
                    throw new RuntimeException('Invalid batching date format.');
                }
                if (!str_contains($batchingDate, ':')) {
                    $batchingDate .= ' 00:00:00';
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $batchingDate)) {
                    $batchingDate .= ':00';
                }
                if (!$canUseSupplierBatch) {
                    $supplierBatchNo = '';
                }
                if (!$canUseSupplierIdentityAndPrice) {
                    $supplierName = '';
                    $supplierCode = '';
                    $currentPrice = '';
                }
                $priceVal = null;
                if ($currentPrice !== '') {
                    $priceVal = (float) $currentPrice;
                    if ($priceVal < 0) {
                        throw new RuntimeException('Price cannot be negative.');
                    }
                }
                if (strlen($unit) > 32) {
                    throw new RuntimeException('Unit is too long.');
                }
                if (strlen($supplierBatchNo) > 120) {
                    throw new RuntimeException('Supplier batch no is too long.');
                }

                $pdo->beginTransaction();
                $stStock = $pdo->prepare('SELECT id FROM stocks WHERE dept_id = ? AND name = ? LIMIT 1');
                $stStock->execute([$deptId, $stockName]);
                $stockId = (int) $stStock->fetchColumn();

                if ($stockId > 0) {
                    if ($canUseSupplierIdentityAndPrice) {
                        $upd = $pdo->prepare(
                            'UPDATE stocks
                             SET unit = ?, supplier_name = ?, supplier_code = ?, current_price = COALESCE(?, current_price)
                             WHERE id = ?'
                        );
                        $upd->execute([
                            $unit,
                            $supplierName === '' ? null : $supplierName,
                            $supplierCode === '' ? null : $supplierCode,
                            $priceVal,
                            $stockId,
                        ]);
                    } else {
                        $upd = $pdo->prepare('UPDATE stocks SET unit = ? WHERE id = ?');
                        $upd->execute([$unit, $stockId]);
                    }
                } else {
                    $insStock = $pdo->prepare(
                        'INSERT INTO stocks (name, dept_id, unit, current_price, supplier_name, supplier_code, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insStock->execute([
                        $stockName,
                        $deptId,
                        $unit,
                        $priceVal ?? 0,
                        $canUseSupplierIdentityAndPrice && $supplierName !== '' ? $supplierName : null,
                        $canUseSupplierIdentityAndPrice && $supplierCode !== '' ? $supplierCode : null,
                        $userId,
                    ]);
                    $stockId = (int) $pdo->lastInsertId();
                }

                $insEntry = $pdo->prepare(
                    'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insEntry->execute([
                    $stockId,
                    $deptId,
                    $qty,
                    $batchingDate,
                    $supplierBatchNo === '' ? null : $supplierBatchNo,
                    $userId,
                ]);
                $pdo->commit();
                $notice = 'Stock entry saved.';
            } elseif ($action === 'request_edit') {
                if (!$canRequestEdits) {
                    throw new RuntimeException('Only TL and Staff can submit edit requests.');
                }
                $entryId = (int) ($_POST['entry_id'] ?? 0);
                $fieldName = trim((string) ($_POST['field_name'] ?? ''));
                $newValue = trim((string) ($_POST['new_value'] ?? ''));
                $reason = trim((string) ($_POST['reason'] ?? ''));

                $allowedFields = ['qty', 'batching_date', 'supplier_batch_no'];
                if ($entryId < 1) {
                    throw new RuntimeException('Invalid entry ID.');
                }
                if (!in_array($fieldName, $allowedFields, true)) {
                    throw new RuntimeException('Invalid field for edit request.');
                }
                if ($reason === '') {
                    throw new RuntimeException('Reason is required.');
                }

                $sql = 'SELECT se.id, se.dept_id, se.entered_by, se.qty, se.batching_date, se.supplier_batch_no
                        FROM stock_entries se
                        WHERE se.id = ?';
                if (ims_is_staff($role)) {
                    $sql .= ' AND se.dept_id = ? AND se.entered_by = ?';
                } else {
                    $sql .= ' AND se.dept_id = ?';
                }
                $stEntry = $pdo->prepare($sql);
                if (ims_is_staff($role)) {
                    $stEntry->execute([$entryId, $userDeptId, $userId]);
                } else {
                    $stEntry->execute([$entryId, $userDeptId]);
                }
                $entry = $stEntry->fetch();
                if (!$entry) {
                    throw new RuntimeException('Entry not found or out of scope.');
                }

                $stPending = $pdo->prepare(
                    'SELECT COUNT(*) FROM edit_requests
                     WHERE table_name = ? AND record_id = ? AND status = ?'
                );
                $stPending->execute(['stock_entries', $entryId, 'pending']);
                if ((int) $stPending->fetchColumn() > 0) {
                    throw new RuntimeException('This entry already has a pending edit request.');
                }

                $oldValue = match ($fieldName) {
                    'qty' => (string) $entry['qty'],
                    'batching_date' => (string) $entry['batching_date'],
                    'supplier_batch_no' => (string) ($entry['supplier_batch_no'] ?? ''),
                    default => '',
                };

                $insReq = $pdo->prepare(
                    'INSERT INTO edit_requests (table_name, record_id, field_name, old_value, new_value, reason, requested_by, status)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $insReq->execute([
                    'stock_entries',
                    $entryId,
                    $fieldName,
                    $oldValue,
                    $newValue,
                    $reason,
                    $userId,
                    'pending',
                ]);
                $notice = 'Edit request submitted.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$departments = [];
$entries = [];
$pendingByEntry = [];
$myRequests = [];

try {
    $stDept = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    $departments = $stDept ? $stDept->fetchAll() : [];

    $sql = 'SELECT
              se.id,
              se.qty,
              se.batching_date,
              se.supplier_batch_no,
              se.created_at,
              s.name AS stock_name,
              s.unit,
              s.current_price,
              s.supplier_name,
              s.supplier_code,
              d.name AS dept_name,
              u.name AS entered_by_name
            FROM stock_entries se
            INNER JOIN stocks s ON s.id = se.stock_id
            INNER JOIN departments d ON d.id = se.dept_id
            INNER JOIN users u ON u.id = se.entered_by';
    $args = [];
    if (ims_is_staff($role)) {
        $sql .= ' WHERE se.dept_id = ? AND se.entered_by = ?';
        $args = [$userDeptId, $userId];
    } elseif (ims_is_tl($role)) {
        $sql .= ' WHERE se.dept_id = ?';
        $args = [$userDeptId];
    }
    $sql .= ' ORDER BY se.created_at DESC, se.id DESC';
    $stEntries = $pdo->prepare($sql);
    $stEntries->execute($args);
    $entries = $stEntries->fetchAll();

    if ($entries !== []) {
        $ids = array_map(static fn(array $r): int => (int) $r['id'], $entries);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stPendingMap = $pdo->prepare(
            "SELECT record_id, COUNT(*) AS cnt
             FROM edit_requests
             WHERE table_name = 'stock_entries' AND status = 'pending' AND record_id IN ($in)
             GROUP BY record_id"
        );
        $stPendingMap->execute($ids);
        foreach ($stPendingMap->fetchAll() as $row) {
            $pendingByEntry[(int) $row['record_id']] = (int) $row['cnt'];
        }
    }

    if ($canRequestEdits) {
        $stMine = $pdo->prepare(
            'SELECT er.id, er.table_name, er.record_id, er.field_name, er.old_value, er.new_value,
                    er.reason, er.status, er.rejection_note, er.reviewed_at, er.created_at,
                    ru.name AS reviewed_by_name
             FROM edit_requests er
             LEFT JOIN users ru ON ru.id = er.reviewed_by
             WHERE er.requested_by = ?
             ORDER BY er.created_at DESC, er.id DESC'
        );
        $stMine->execute([$userId]);
        $myRequests = $stMine->fetchAll();
    }
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load page data. Run sql/migration_phase2_inventory_stocks.sql.';
}

$deptNameMap = [];
foreach ($departments as $d) {
    $deptNameMap[(int) $d['id']] = (string) $d['name'];
}
$pageTitle = 'Stock Entries';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
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
    input, select, textarea { width:100%; padding:0.5rem 0.55rem; border:1px solid var(--border); border-radius:8px; background:#fffdfa; }
    textarea { min-height:72px; resize:vertical; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.45rem 0.7rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .muted { color:var(--muted); }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
    .pill { display:inline-flex; align-items:center; font-size:0.75rem; border:1px solid var(--border); border-radius:999px; padding:0.14rem 0.48rem; background:#fff9ef; }
    .pill.pending { border-color:#d1b24f; color:#7a5e05; background:#fff8de; }
    .pill.approved { border-color:#5aa46c; color:#1f6a33; background:#ecfff1; }
    .pill.rejected { border-color:#c88383; color:#8f2e2e; background:#fff0f0; }
    table { width:100%; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.2rem;font-size:1.25rem;">Stock Entries</h1>
    <p class="muted" style="margin:0 0 0.55rem;">
      Logged in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
      (<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>)
      <?php if (!$canViewAll): ?>
        — Department: <strong><?= htmlspecialchars($deptNameMap[$userDeptId] ?? ('#' . $userDeptId), ENT_QUOTES, 'UTF-8') ?></strong>
      <?php endif; ?>
    </p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
      <?php if (ims_is_executive($role)): ?>
        <a class="btn" href="../exec/edit_requests.php">Review edit requests</a>
      <?php endif; ?>
      <a class="btn" href="../auth/settings.php">Settings</a>
      <a class="btn" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <?php if ($canAddStockEntries): ?>
    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Add stock entry</h2>
      <form method="post" action="stock_entries.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="add_entry">
        <div class="row">
          <?php if ($canViewAll): ?>
            <div>
              <label for="dept_id">Department</label>
              <select id="dept_id" name="dept_id" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $d): ?>
                  <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars((string) $d['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div>
            <label for="stock_name">Stock name</label>
            <input id="stock_name" name="stock_name" required maxlength="160">
          </div>
          <div>
            <label for="unit">Unit</label>
            <input id="unit" name="unit" maxlength="32" value="pcs">
          </div>
          <div>
            <label for="qty">Qty</label>
            <input id="qty" name="qty" type="number" min="0.01" step="0.01" required>
          </div>
          <div>
            <label for="batching_date">Batching date</label>
            <input id="batching_date" name="batching_date" type="datetime-local" required>
          </div>
          <?php if ($canUseSupplierBatch): ?>
            <div>
              <label for="supplier_batch_no">Supplier batch no</label>
              <input id="supplier_batch_no" name="supplier_batch_no" maxlength="120">
            </div>
          <?php endif; ?>
          <?php if ($canUseSupplierIdentityAndPrice): ?>
            <div>
              <label for="supplier_name">Supplier name</label>
              <input id="supplier_name" name="supplier_name" maxlength="160">
            </div>
            <div>
              <label for="supplier_code">Supplier code</label>
              <input id="supplier_code" name="supplier_code" maxlength="64">
            </div>
            <div>
              <label for="current_price">Price</label>
              <input id="current_price" name="current_price" type="number" min="0" step="0.01">
            </div>
          <?php endif; ?>
          <div style="display:flex;align-items:flex-end;">
            <button class="btn primary" type="submit" style="width:100%;">Save entry</button>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Stock entries</h2>
    <table id="entriesTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Stock</th>
          <th>Qty</th>
          <th>Batching date</th>
          <th>Supplier batch no</th>
          <th>Entered by</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $row): ?>
          <?php $eid = (int) $row['id']; ?>
          <tr>
            <td><?= $eid ?></td>
            <td><?= htmlspecialchars((string) $row['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <strong><?= htmlspecialchars((string) $row['stock_name'], ENT_QUOTES, 'UTF-8') ?></strong>
              <div class="muted" style="font-size:0.8rem;">
                Unit: <?= htmlspecialchars((string) $row['unit'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($canUseSupplierIdentityAndPrice): ?>
                  | Supplier: <?= htmlspecialchars((string) ($row['supplier_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                  | Code: <?= htmlspecialchars((string) ($row['supplier_code'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                  | Price: <?= number_format((float) $row['current_price'], 2) ?>
                <?php endif; ?>
              </div>
            </td>
            <td><?= htmlspecialchars((string) $row['qty'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $row['batching_date'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($row['supplier_batch_no'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $row['entered_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if (!$canRequestEdits): ?>
                <span class="muted">—</span>
              <?php elseif (!empty($pendingByEntry[$eid])): ?>
                <span class="pill pending">Pending request</span>
              <?php else: ?>
                <details>
                  <summary class="btn" style="display:inline-block;">Request edit</summary>
                  <form method="post" action="stock_entries.php" style="margin-top:0.45rem;min-width:220px;">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="request_edit">
                    <input type="hidden" name="entry_id" value="<?= $eid ?>">
                    <label>Field</label>
                    <select name="field_name" required>
                      <option value="qty">qty</option>
                      <option value="batching_date">batching_date</option>
                      <option value="supplier_batch_no">supplier_batch_no</option>
                    </select>
                    <label>New value</label>
                    <input name="new_value" required>
                    <label>Reason</label>
                    <textarea name="reason" required></textarea>
                    <button class="btn primary" type="submit" style="margin-top:0.45rem;">Submit request</button>
                  </form>
                </details>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($canRequestEdits): ?>
    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">My edit requests</h2>
      <table id="myRequestsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Target</th>
            <th>Change</th>
            <th>Status</th>
            <th>Reason</th>
            <th>Rejection note</th>
            <th>Reviewed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myRequests as $r): ?>
            <tr>
              <td><?= (int) $r['id'] ?></td>
              <td><?= htmlspecialchars((string) $r['table_name'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $r['record_id'] ?></td>
              <td>
                <strong><?= htmlspecialchars((string) $r['field_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                <span class="muted">From: <?= htmlspecialchars((string) ($r['old_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span><br>
                <span class="muted">To: <?= htmlspecialchars((string) ($r['new_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td><span class="pill <?= htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars((string) $r['reason'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($r['rejection_note'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?= htmlspecialchars((string) ($r['reviewed_by_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?><br>
                <span class="muted"><?= htmlspecialchars((string) ($r['reviewed_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  $('#entriesTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });
  if ($('#myRequestsTable').length) {
    $('#myRequestsTable').DataTable({ pageLength: 10, order: [[0, 'desc']] });
  }
});
  </script>
</body>
</html>
