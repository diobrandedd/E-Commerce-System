<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/ims_exec_sidebar.php';
requireRole([IMS_ROLE_EXECUTIVE], '../auth/login.php');

ims_session_start();
$user = ims_current_user();
$userId = (int) ($user['id'] ?? 0);

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
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $decision = trim((string) ($_POST['decision'] ?? ''));
        $rejectionNote = trim((string) ($_POST['rejection_note'] ?? ''));

        try {
            if ($requestId < 1) {
                throw new RuntimeException('Invalid request id.');
            }
            if (!in_array($decision, ['approve', 'reject'], true)) {
                throw new RuntimeException('Invalid decision.');
            }

            $pdo->beginTransaction();
            $stReq = $pdo->prepare(
                'SELECT * FROM edit_requests WHERE id = ? AND status = ? LIMIT 1 FOR UPDATE'
            );
            $stReq->execute([$requestId, 'pending']);
            $req = $stReq->fetch();
            if (!$req) {
                throw new RuntimeException('Request already reviewed or not found.');
            }

            if ($decision === 'reject') {
                if ($rejectionNote === '') {
                    throw new RuntimeException('Rejection note is required when rejecting a request.');
                }
                $upd = $pdo->prepare(
                    'UPDATE edit_requests
                     SET status = ?, rejection_note = ?, reviewed_by = ?, reviewed_at = NOW()
                     WHERE id = ?'
                );
                $upd->execute(['rejected', $rejectionNote, $userId, $requestId]);
                $pdo->commit();
                $notice = 'Request rejected.';
            } else {
                $tableName = (string) $req['table_name'];
                $recordId = (int) $req['record_id'];
                $fieldName = (string) $req['field_name'];
                $newValue = (string) ($req['new_value'] ?? '');

                if ($tableName !== 'stock_entries') {
                    throw new RuntimeException('Unsupported table in request.');
                }
                $allowedFields = ['qty', 'batching_date', 'supplier_batch_no'];
                if (!in_array($fieldName, $allowedFields, true)) {
                    throw new RuntimeException('Unsupported field in request.');
                }

                $finalValue = $newValue;
                if ($fieldName === 'qty') {
                    if (!is_numeric($newValue) || (float) $newValue <= 0) {
                        throw new RuntimeException('qty must be a positive number.');
                    }
                    $finalValue = (string) ((float) $newValue);
                } elseif ($fieldName === 'batching_date') {
                    $tmp = str_replace('T', ' ', $newValue);
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $tmp)) {
                        throw new RuntimeException('Invalid batching_date format.');
                    }
                    if (!str_contains($tmp, ':')) {
                        $tmp .= ' 00:00:00';
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $tmp)) {
                        $tmp .= ':00';
                    }
                    $finalValue = $tmp;
                } elseif ($fieldName === 'supplier_batch_no') {
                    if (strlen($newValue) > 120) {
                        throw new RuntimeException('supplier_batch_no is too long.');
                    }
                    $finalValue = $newValue;
                }

                $updEntry = $pdo->prepare("UPDATE stock_entries SET {$fieldName} = ? WHERE id = ?");
                $updEntry->execute([$finalValue === '' ? null : $finalValue, $recordId]);

                $updReq = $pdo->prepare(
                    'UPDATE edit_requests
                     SET status = ?, rejection_note = NULL, reviewed_by = ?, reviewed_at = NOW()
                     WHERE id = ?'
                );
                $updReq->execute(['approved', $userId, $requestId]);

                $pdo->commit();
                $notice = 'Request approved and record updated.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$requests = [];
try {
    $st = $pdo->query(
        'SELECT er.id, er.table_name, er.record_id, er.field_name, er.old_value, er.new_value,
                er.reason, er.status, er.rejection_note, er.reviewed_at, er.created_at,
                req.name AS requested_by_name, rev.name AS reviewed_by_name
         FROM edit_requests er
         INNER JOIN users req ON req.id = er.requested_by
         LEFT JOIN users rev ON rev.id = er.reviewed_by
         ORDER BY (er.status = "pending") DESC, er.created_at DESC, er.id DESC'
    );
    $requests = $st ? $st->fetchAll() : [];
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load requests. Run sql/migration_phase2_inventory_stocks.sql.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Requests Review</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); }
    .layout { display:grid; grid-template-columns:260px 1fr; min-height:100vh; }
    .exec-sidebar { background:#fff9ef; border-right:1px solid var(--border); padding:1rem 0.85rem; }
    .exec-sidebar-title { margin:0 0 0.65rem; font-size:1rem; color:var(--muted); }
    .exec-sidebar-nav { display:flex; flex-direction:column; gap:0.35rem; }
    .exec-sidebar-link { display:flex; justify-content:space-between; align-items:center; text-decoration:none; border:1px solid var(--border); border-radius:8px; padding:0.5rem 0.65rem; background:#fffdfa; color:var(--text); font-weight:600; }
    .exec-sidebar-link.active { border-color:var(--accent); color:var(--accent); background:#fff7e8; }
    .exec-sidebar-badge { font-size:0.75rem; border:1px solid #d1b24f; border-radius:999px; padding:0.05rem 0.4rem; color:#7a5e05; background:#fff8de; }
    .content { padding:1rem; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:0.95rem 1rem; margin-bottom:0.9rem; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.4rem 0.65rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .btn.danger { border-color:#d6a1a1; color:#7a2f2f; background:#fff1f1; }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
    .pill { display:inline-flex; align-items:center; font-size:0.75rem; border:1px solid var(--border); border-radius:999px; padding:0.14rem 0.48rem; background:#fff9ef; }
    .pill.pending { border-color:#d1b24f; color:#7a5e05; background:#fff8de; }
    .pill.approved { border-color:#5aa46c; color:#1f6a33; background:#ecfff1; }
    .pill.rejected { border-color:#c88383; color:#8f2e2e; background:#fff0f0; }
    textarea { width:100%; min-height:72px; border:1px solid var(--border); border-radius:8px; padding:0.45rem; background:#fffdfa; resize:vertical; }
    @media (max-width:960px) {
      .layout { grid-template-columns:1fr; }
      .exec-sidebar { border-right:0; border-bottom:1px solid var(--border); }
    }
  </style>
</head>
<body>
  <div class="layout">
    <?php ims_render_exec_sidebar('edit_requests'); ?>
    <main class="content">
  <div class="card">
    <h1 style="margin:0 0 0.25rem;font-size:1.25rem;">Executive Review - Edit Requests</h1>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  </div>

  <div class="card">
    <table id="requestsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Target</th>
          <th>Change</th>
          <th>Requested by</th>
          <th>Status</th>
          <th>Reason</th>
          <th>Review</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <?php $isPending = ((string) $r['status'] === 'pending'); ?>
          <tr>
            <td><?= (int) $r['id'] ?></td>
            <td><?= htmlspecialchars((string) $r['table_name'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $r['record_id'] ?></td>
            <td>
              <strong><?= htmlspecialchars((string) $r['field_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
              <span style="font-size:0.82rem;color:#7e6a4b;">From: <?= htmlspecialchars((string) ($r['old_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span><br>
              <span style="font-size:0.82rem;color:#7e6a4b;">To: <?= htmlspecialchars((string) ($r['new_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td><?= htmlspecialchars((string) $r['requested_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="pill <?= htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>
              <?= htmlspecialchars((string) $r['reason'], ENT_QUOTES, 'UTF-8') ?>
              <?php if ((string) $r['status'] === 'rejected'): ?>
                <div style="margin-top:0.35rem;color:#8f2e2e;"><strong>Rejection note:</strong> <?= htmlspecialchars((string) ($r['rejection_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($isPending): ?>
                <form method="post" action="edit_requests.php" style="margin-bottom:0.35rem;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="decision" value="approve">
                  <button class="btn primary" type="submit">Approve</button>
                </form>
                <form method="post" action="edit_requests.php">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="decision" value="reject">
                  <label style="font-size:0.78rem;color:#7e6a4b;">Rejection note (required)</label>
                  <textarea name="rejection_note" required></textarea>
                  <button class="btn danger" type="submit" style="margin-top:0.35rem;">Reject</button>
                </form>
              <?php else: ?>
                <?= htmlspecialchars((string) ($r['reviewed_by_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?><br>
                <span style="font-size:0.82rem;color:#7e6a4b;"><?= htmlspecialchars((string) ($r['reviewed_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  $('#requestsTable').DataTable({
    pageLength: 25,
    order: [[0, 'desc']]
  });
});
  </script>
    </main>
  </div>
</body>
</html>
