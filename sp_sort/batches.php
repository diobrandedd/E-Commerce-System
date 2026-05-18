<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pdo = rts_pdo();
$doneMsg = isset($_GET['done']);

$filterStatus = $_GET['status'] ?? '';
$filterCourier = $_GET['courier'] ?? '';
$filterShop = $_GET['shop'] ?? '';

$sql = 'SELECT b.id, b.row_count, b.status, b.completed_at, b.created_at, b.source_files,
               u.username AS submitted_by
        FROM sp_sort_batches b
        INNER JOIN app_users u ON u.id = b.submitted_by_user_id
        WHERE 1=1';
$params = [];
if ($filterStatus === 'completed' || $filterStatus === 'in_progress') {
    $sql .= ' AND b.status = ?';
    $params[] = $filterStatus;
}
$sql .= ' ORDER BY b.created_at DESC LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($params);
$batches = $st->fetchAll(PDO::FETCH_ASSOC);

$rowSql = 'SELECT r.id, r.batch_id, r.row_num, r.sort_key_d, r.courier, r.shop, r.channel_text,
                  b.status AS batch_status, b.completed_at, b.created_at AS batch_created
           FROM sp_sort_rows r
           INNER JOIN sp_sort_batches b ON b.id = r.batch_id
           WHERE 1=1';
$rowParams = [];
if ($filterCourier !== '' && in_array($filterCourier, ['spx', 'jnt', 'flash', 'unknown'], true)) {
    $rowSql .= ' AND r.courier = ?';
    $rowParams[] = $filterCourier;
}
if ($filterShop !== '' && in_array($filterShop, ['shopee', 'lazada', 'tiktok', 'unknown'], true)) {
    $rowSql .= ' AND r.shop = ?';
    $rowParams[] = $filterShop;
}
if ($filterStatus === 'completed' || $filterStatus === 'in_progress') {
    $rowSql .= ' AND b.status = ?';
    $rowParams[] = $filterStatus;
}
$rowSql .= ' ORDER BY r.id DESC LIMIT 3000';
$rowSt = $pdo->prepare($rowSql);
$rowSt->execute($rowParams);
$allRows = $rowSt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'SP Sort — history';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="sp_sort.css">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar_styles.php'; ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    .filters { display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; margin-bottom:1rem; }
    .filters label { font-size:0.8rem; color:var(--muted); display:block; }
    .filters select { padding:0.35rem 0.5rem; border-radius:8px; border:1px solid var(--border); }
    table.dataTable { font-size:0.85rem; }
    .badge { font-size:0.72rem; padding:0.15rem 0.45rem; border-radius:6px; }
    .badge-done { background:#dcfce7; color:#166534; }
    .badge-progress { background:#fef3c7; color:#92400e; }
    .courier-flash { background:#fff9c4; }
    .courier-spx { background:#ffe0b2; }
  </style>
</head>
<body class="admin-layout">
<?php rts_admin_sidebar('sp_sort_records', '../', sp_sort_delivery_nav_badge()); ?>
<main>
<div class="wrap">
  <h1 style="margin:0 0 0.75rem;font-size:1.15rem;">SP Sort records</h1>

  <?php if ($doneMsg): ?>
    <div class="msg msg-success">Batch marked as done and saved to history.</div>
  <?php endif; ?>

  <form class="filters card" method="get">
    <div>
      <label>Batch status</label>
      <select name="status">
        <option value="">All</option>
        <option value="completed"<?= $filterStatus === 'completed' ? ' selected' : '' ?>>Completed</option>
        <option value="in_progress"<?= $filterStatus === 'in_progress' ? ' selected' : '' ?>>In progress</option>
      </select>
    </div>
    <div>
      <label>Courier (rows)</label>
      <select name="courier">
        <option value="">All</option>
        <?php foreach (['spx', 'jnt', 'flash', 'unknown'] as $c): ?>
        <option value="<?= $c ?>"<?= $filterCourier === $c ? ' selected' : '' ?>><?= htmlspecialchars(rts_courier_label($c), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Shop (rows)</label>
      <select name="shop">
        <option value="">All</option>
        <?php foreach (['shopee', 'lazada', 'tiktok', 'unknown'] as $s): ?>
        <option value="<?= $s ?>"<?= $filterShop === $s ? ' selected' : '' ?>><?= htmlspecialchars(rts_shop_label($s), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn">Apply filters</button>
  </form>

  <div class="card">
    <h2 style="margin:0 0 0.75rem;font-size:1rem;">Batches</h2>
    <table id="batchesTable" class="display" style="width:100%">
      <thead>
        <tr>
          <th>ID</th>
          <th>Rows</th>
          <th>Status</th>
          <th>Created</th>
          <th>Completed</th>
          <th>By</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($batches as $b): ?>
        <tr>
          <td><?= (int) $b['id'] ?></td>
          <td><?= (int) $b['row_count'] ?></td>
          <td>
            <?php if (($b['status'] ?? '') === 'completed'): ?>
              <span class="badge badge-done">Done</span>
            <?php else: ?>
              <span class="badge badge-progress">In progress</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string) $b['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($b['completed_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $b['submitted_by'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="workflow.php?batch=<?= (int) $b['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card" style="margin-top:1rem;">
    <h2 style="margin:0 0 0.75rem;font-size:1rem;">Delivered items (rows)</h2>
    <table id="rowsTable" class="display" style="width:100%">
      <thead>
        <tr>
          <th>Batch</th>
          <th>Row</th>
          <th>Col D</th>
          <th>Shop</th>
          <th>Courier</th>
          <th>Channel</th>
          <th>Batch status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allRows as $r):
            $courier = (string) $r['courier'];
            $rowClass = $courier === 'flash' ? 'courier-flash' : ($courier === 'spx' ? 'courier-spx' : '');
            ?>
        <tr class="<?= htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') ?>">
          <td><?= (int) $r['batch_id'] ?></td>
          <td><?= (int) $r['row_num'] ?></td>
          <td><?= htmlspecialchars((string) $r['sort_key_d'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars(rts_shop_label((string) $r['shop']), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars(rts_courier_label($courier), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($r['channel_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $r['batch_status'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</main>
<?php rts_admin_sidebar_script(); ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
  $('#batchesTable').DataTable({ order: [[0, 'desc']], pageLength: 25 });
  $('#rowsTable').DataTable({ order: [[0, 'desc']], pageLength: 50 });
});
</script>
</body>
</html>
