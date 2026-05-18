<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/deliveries_review_nav.php';
require_once dirname(__DIR__) . '/includes/admin_nav.php';

rts_require_roles([RTS_ROLE_ADMIN], '../login.php');

$pageTitle = 'Deliveries — admin review';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$from = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$to = isset($_GET['to']) ? trim((string) $_GET['to']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$size = isset($_GET['size']) && (int) $_GET['size'] === 20 ? 20 : 10;
$voidOnly = isset($_GET['void_only']) && $_GET['void_only'] === '1';

if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = '';
}
if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = '';
}

$err = null;
$rows = [];
$total = 0;
$deliveryNavBadge = '';

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database error.';
    exit;
}

try {
    $deliveryNavBadge = rts_delivery_review_nav_badge_html($pdo);

    $where = ['1=1'];
    $params = [];

    if ($voidOnly) {
        $where[] = 'i.voided_at IS NOT NULL';
    }

    if ($from !== '') {
        $where[] = 'b.delivery_date >= ?';
        $params[] = $from;
    }
    if ($to !== '') {
        $where[] = 'b.delivery_date <= ?';
        $params[] = $to;
    }
    if ($q !== '') {
        $where[] = '(i.tracking_number LIKE ? OR i.photo_filename LIKE ? OR CAST(i.id AS CHAR) = ? OR CAST(i.batch_id AS CHAR) = ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $q;
        $params[] = $q;
    }

    $whereSql = implode(' AND ', $where);

    $dupSub = '(SELECT COUNT(*) FROM delivery_batch_items x
        WHERE x.voided_at IS NULL
        AND TRIM(COALESCE(x.tracking_number, \'\')) != \'\'
        AND TRIM(COALESCE(x.tracking_number, \'\')) = TRIM(COALESCE(i.tracking_number, \'\'))
        )';

    $countSql = "SELECT COUNT(*) FROM delivery_batch_items i
        INNER JOIN delivery_batches b ON b.id = i.batch_id
        WHERE {$whereSql}";
    $stc = $pdo->prepare($countSql);
    $stc->execute($params);
    $total = (int) $stc->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $size));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $size;

    if ($voidOnly) {
        $orderSql = 'ORDER BY i.voided_at DESC, i.id DESC';
    } else {
        $orderSql = "ORDER BY (i.voided_at IS NOT NULL) ASC,
            (CASE
                WHEN i.voided_at IS NULL AND (i.tracking_number IS NULL OR TRIM(COALESCE(i.tracking_number, '')) = '') THEN 0
                WHEN i.voided_at IS NULL AND TRIM(COALESCE(i.tracking_number, '')) != '' AND {$dupSub} > 1 THEN 1
                ELSE 2
            END) ASC,
            b.delivery_date DESC,
            i.id DESC";
    }

    $dataSql = "SELECT i.id AS item_id, i.batch_id, i.photo_filename, i.item_status, i.tracking_number, i.raw_qr_text, i.voided_at,
        b.delivery_date,
        {$dupSub} AS dup_same_tracking
        FROM delivery_batch_items i
        INNER JOIN delivery_batches b ON b.id = i.batch_id
        WHERE {$whereSql}
        {$orderSql}
        LIMIT " . (int) $size . ' OFFSET ' . (int) $offset;

    $std = $pdo->prepare($dataSql);
    $std->execute($params);
    $rows = $std->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $err = 'Tables may be missing. Import sql/migration_auth_and_deliveries.sql';
}

$qs = static function (array $extra) use ($q, $from, $to, $size, $voidOnly): string {
    $p = array_merge([
        'q' => $q,
        'from' => $from,
        'to' => $to,
        'size' => (string) $size,
        'void_only' => $voidOnly ? '1' : '',
    ], $extra);
    $p = array_filter($p, static fn ($v) => $v !== null && $v !== '');

    return htmlspecialchars(http_build_query($p), ENT_QUOTES, 'UTF-8');
};

$iconEye = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
$iconPen = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root {
      --bg:#fffdf6; --panel:#ffffff; --border:#e8dcc5; --text:#3f2f1e;
      --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#34a853;
      --row-miss:#fde8e8;
      --row-dup:#fff4d6;
    }
    * { box-sizing: border-box; }
    body {
      margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text);
      line-height:1.45; display:flex; min-height:100vh; opacity:1; transition:opacity .22s ease;
      overflow-x:hidden;
    }
    body.page-enter { animation: pageIn .28s ease both; }
    body.page-exit { opacity:0; }
    .sidebar {
      width:220px; padding:1.25rem 1rem; border-right:1px solid var(--border); background:#fff5df;
      display:flex; flex-direction:column; gap:1.25rem; position:sticky; top:0; align-self:flex-start;
      height:100vh; overflow-y:auto;
    }
    .sidebar h1 { margin:0; font-size:1.05rem; font-weight:650; }
    .nav-links a {
      display:block; padding:0.4rem 0.1rem; color:var(--muted); text-decoration:none; font-size:0.9rem;
    }
    .nav-links a.active { color:var(--accent); font-weight:600; }
    .nav-links a:hover { color:#4a381d; }
    main {
      flex:1; min-width:0; max-width:1160px; width:100%; margin:0 auto;
      padding:1rem 1.25rem 2.5rem; overflow-x:hidden;
    }
    .card {
      background:var(--panel); border:1px solid var(--border); border-radius:12px;
      padding:1rem 1.15rem; margin-bottom:1rem;
    }
    .card.table-card { animation: tablePageIn .38s ease both; min-width:0; max-width:100%; }
    .card h2 { margin:0 0 0.75rem; font-size:1rem; font-weight:650; }
    .muted { color:var(--muted); font-size:0.85rem; }
    .mono { font-family:ui-monospace,Consolas,monospace; font-size:0.85rem; }
    .filters {
      display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; margin-bottom:1rem;
    }
    .filters label { font-size:0.76rem; color:var(--muted); display:block; margin-bottom:0.2rem; }
    .filters input[type="text"], .filters input[type="search"], .filters input[type="date"], .filters select {
      padding:0.45rem 0.55rem; border-radius:8px; border:1px solid var(--border); background:#fffdfa;
      font-size:0.9rem; min-width:140px;
    }
    .filters .q-track { min-width:220px; border-color:var(--accent); }
    .pager { display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; justify-content:space-between; margin-top:0.75rem; }
    .pager a, .pager span { font-size:0.88rem; }
    .pager a.pager-link { text-decoration:none; color:var(--accent); font-weight:600; }
    .pager a.pager-link:hover { text-decoration:underline; }
    .btn {
      padding:0.45rem 0.85rem; border-radius:8px; border:none; background:var(--accent);
      color:#061018; font-weight:600; cursor:pointer; font-size:0.88rem;
    }
    .btn-ghost { background:transparent; border:1px solid var(--border); color:var(--text); font-weight:500; }
    .btn-danger { border-color:var(--danger); color:var(--danger); }
    .btn-void-toggle { border-color:#9a6b6b; color:#7a3e3e; background:#fff5f5; }
    .btn-void-toggle.on { background:#5c2a2a; color:#fff; border-color:#5c2a2a; }
    .records-table-wrap {
      width:100%;
      max-width:100%;
      overflow-x:auto;
      overflow-y:visible;
      -webkit-overflow-scrolling:touch;
      margin-top:0.25rem;
    }
    table.data-table,
    table.records-table {
      width:100%;
      min-width:640px;
      border-collapse:collapse;
      font-size:0.88rem;
    }
    table.data-table th,
    table.data-table td,
    table.records-table th,
    table.records-table td {
      text-align:left; padding:0.5rem 0.4rem; border-bottom:1px solid var(--border); vertical-align:middle;
      white-space:nowrap;
    }
    table.data-table th,
    table.records-table th {
      color:var(--muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:0.03em;
    }
    table.data-table td.td-tracking,
    table.records-table td.td-tracking { white-space:normal; min-width:9rem; }
    tr.row-missing td { background:var(--row-miss); }
    tr.row-dup td { background:var(--row-dup); }
    tr.row-missing.row-dup td { background:linear-gradient(90deg, var(--row-miss) 0%, var(--row-dup) 100%); }
    tr.row-voided { opacity:0.72; }
    tr.row-voided .thumb { filter:grayscale(0.35); }
    .pill { display:inline-block; padding:0.1rem 0.4rem; border-radius:6px; font-size:0.75rem; border:1px solid var(--border); }
    .pill.dup-warn { background:#fff0c2; border-color:#e6b422; color:#6a5200; }
    .thumb { width:52px; height:52px; object-fit:cover; border-radius:8px; border:1px solid var(--border); display:block; }
    .icon-btn {
      display:inline-flex; align-items:center; justify-content:center; width:2.1rem; height:2.1rem;
      padding:0; border:1px solid var(--border); border-radius:8px; background:#fffdfa; color:var(--text);
      cursor:pointer; vertical-align:middle;
    }
    .icon-btn:hover { border-color:var(--muted); background:#fff7ea; }
    .icon-btn + .icon-btn { margin-left:0.35rem; }
    .actions-cell { white-space:nowrap; }
    .hidden { display:none !important; }
    .void-confirm-backdrop {
      position:fixed; inset:0; background:rgba(75,58,29,0.45); z-index:150;
      display:flex; align-items:center; justify-content:center; padding:1rem;
    }
    .void-confirm-modal {
      background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.2rem 1.35rem;
      max-width:420px; width:100%; box-shadow:0 16px 48px rgba(101,76,36,0.22);
    }
    .void-confirm-modal h3 { margin:0 0 0.5rem; font-size:1.05rem; color:#6b3030; }
    .void-confirm-modal .void-confirm-summary { font-size:0.9rem; line-height:1.5; margin:0 0 1rem; }
    .void-confirm-actions { display:flex; flex-wrap:wrap; gap:0.5rem; justify-content:flex-end; }
    .void-confirm-actions .btn-danger-confirm {
      background:#5c2a2a; border:1px solid #5c2a2a; color:#fff; font-weight:600;
      padding:0.45rem 0.85rem; border-radius:8px; cursor:pointer; font-size:0.88rem;
    }
    .void-confirm-actions .btn-cancel-confirm {
      background:#fffdfa; border:1px solid var(--border); color:var(--text);
      padding:0.45rem 0.85rem; border-radius:8px; cursor:pointer; font-size:0.88rem;
    }
    .modal-backdrop {
      position:fixed; inset:0; background:rgba(75,58,29,0.38); z-index:120;
      display:flex; align-items:flex-start; justify-content:center; padding:1rem; overflow-y:auto;
      animation: backdropFade .22s ease both;
    }
    .modal-backdrop.closing { animation: backdropFadeOut .18s ease both; }
    .modal-panel {
      background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.15rem 1.25rem;
      max-width:560px; width:100%; margin:2vh 0;
      animation: modalPop .3s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .modal-panel.wide { max-width:min(680px, 96vw); }
    .modal-panel.modal-edit-wide { max-width:min(920px, 98vw); }
    .modal-edit-grid {
      display:grid;
      grid-template-columns: minmax(200px, 1fr) minmax(240px, 1fr);
      gap:1.25rem;
      align-items:start;
    }
    @media (max-width: 700px) {
      .modal-edit-grid { grid-template-columns: 1fr; }
    }
    .edit-side-photo {
      position:sticky; top:0.5rem;
    }
    .edit-side-photo img {
      width:100%; max-height:min(72vh, 560px); object-fit:contain; border-radius:10px; border:1px solid var(--border);
      background:#faf7f0;
    }
    .modal-panel img.preview { max-width:100%; border-radius:10px; border:1px solid var(--border); }
    .modal-panel dl { margin:0.5rem 0 0; font-size:0.88rem; }
    .modal-panel dt { color:var(--muted); font-size:0.76rem; margin-top:0.45rem; }
    .modal-panel dd { margin:0.15rem 0 0; word-break:break-word; }
    .modal-nav-row { display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.75rem; align-items:center; }
    .success-overlay {
      position:fixed; inset:0; background:rgba(0,0,0,0.72); z-index:200;
      display:flex; align-items:center; justify-content:center;
      animation: backdropFade .2s ease both;
    }
    .success-modal {
      background:var(--panel); border-radius:16px; border:1px solid var(--border);
      padding:1.2rem 1.4rem 1.3rem; text-align:center; width:min(300px, 90%);
      box-shadow:0 18px 45px rgba(0,0,0,0.45);
      animation: modalPop .32s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .success-check {
      width:56px; height:56px; border-radius:50%; border:3px solid rgba(52,199,89,0.25);
      margin:0 auto 0.6rem; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;
    }
    .success-check::before {
      content:''; width:28px; height:14px; border-left:3px solid var(--ok); border-bottom:3px solid var(--ok);
      transform:rotate(-45deg) scale(0); transform-origin:center;
      animation: check-draw 320ms ease-out forwards 120ms;
    }
    .success-check::after {
      content:''; position:absolute; inset:-1px; border-radius:50%; border:3px solid rgba(52,199,89,0.45);
      transform:scale(0.4); opacity:0; animation: check-pulse 480ms ease-out forwards;
    }
    @keyframes check-draw { from { transform:rotate(-45deg) scale(0); } to { transform:rotate(-45deg) scale(1); } }
    @keyframes check-pulse { 0% { transform:scale(0.4); opacity:0.7; } 100% { transform:scale(1.1); opacity:0; } }
    @keyframes backdropFade { from { opacity:0; } to { opacity:1; } }
    @keyframes backdropFadeOut { from { opacity:1; } to { opacity:0; } }
    @keyframes modalPop { from { opacity:0; transform: translateY(12px) scale(0.96); } to { opacity:1; transform: translateY(0) scale(1); } }
    @keyframes tablePageIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }
    @keyframes pageIn { from { opacity:0; } to { opacity:1; } }
    .mobile-topbar {
      display:none; position:fixed; top:0; left:0; right:0; height:56px; z-index:90;
      background:#fff5df; border-bottom:1px solid var(--border); padding:0 0.85rem;
      align-items:center; gap:0.75rem;
    }
    .mobile-menu-btn {
      background:#fff7ea; border:1px solid var(--border); color:var(--text);
      border-radius:10px; padding:0.45rem 0.6rem; cursor:pointer; font-size:1.05rem; line-height:1;
    }
    .mobile-menu-panel {
      display:block; position:fixed; z-index:95; top:56px; left:0; width:240px; bottom:0;
      background:#fff5df; border-right:1px solid var(--border); padding:1rem 0.9rem; overflow-y:auto;
      transform:translateX(-105%); opacity:0; pointer-events:none;
      transition:transform .22s ease, opacity .22s ease;
    }
    .mobile-menu-panel.open { transform:translateX(0); opacity:1; pointer-events:auto; }
    @media (max-width: 720px) {
      .sidebar { display:none !important; }
      .mobile-topbar { display:flex; }
      main { padding-top:80px; padding-left:0.75rem; padding-right:0.75rem; }
      .filters input[type="search"].q-track { min-width:0; width:100%; }
    }
  </style>
</head>
<body class="page-enter">
  <div class="mobile-topbar">
    <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">☰</button>
    <img src="../assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:28px;width:auto;border-radius:6px;" onerror="this.style.display='none'">
    <div style="font-weight:700;">RTS system</div>
  </div>
  <div id="mobileMenuPanel" class="mobile-menu-panel" aria-hidden="true">
    <nav class="nav-links">
      <?php rts_admin_nav_links('deliveries', '../', $deliveryNavBadge, 'review.php'); ?>
    </nav>
  </div>
  <aside class="sidebar">
    <div>
      <img src="../assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:42px;width:auto;max-width:100%;border-radius:8px;margin-bottom:0.5rem;" onerror="this.style.display='none'">
      <h1>RTS system</h1>
      <p style="margin:0.25rem 0 0;font-size:0.78rem;color:var(--muted);">Returns tracking &amp; reporting</p>
    </div>
    <nav class="nav-links">
      <?php rts_admin_nav_links('deliveries', '../', $deliveryNavBadge, 'review.php'); ?>
    </nav>
  </aside>
  <main>
    <?php if ($err !== null): ?>
      <p class="muted"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
      <div class="card table-card">
        <h2>Delivery photos — line items</h2>
        <p class="muted" style="margin-top:0;">Rows <strong>without tracking</strong> first (red). <strong>Duplicate tracking</strong> next (yellow). Use the eye to view (image always shown, including voided) and the pen to edit.</p>
        <form method="get" action="review.php" class="filters">
          <?php if ($voidOnly): ?>
            <input type="hidden" name="void_only" value="1">
          <?php endif; ?>
          <div>
            <label for="q">Tracking search</label>
            <input class="q-track" type="search" id="q" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Part of tracking, filename, or ID…" autocomplete="off">
          </div>
          <div>
            <label for="from">Delivery date from</label>
            <input type="date" id="from" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div>
            <label for="to">Delivery date to</label>
            <input type="date" id="to" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div>
            <label for="size">Per page</label>
            <select id="size" name="size">
              <option value="10" <?= $size === 10 ? 'selected' : '' ?>>10</option>
              <option value="20" <?= $size === 20 ? 'selected' : '' ?>>20</option>
            </select>
          </div>
          <div>
            <label for="void_only_btn">Voided</label>
            <?php if ($voidOnly): ?>
              <a class="btn btn-ghost btn-void-toggle on" id="void_only_btn" href="review.php?<?= $qs(['void_only' => '']) ?>">Voided only (on)</a>
            <?php else: ?>
              <a class="btn btn-ghost btn-void-toggle" id="void_only_btn" href="review.php?<?= $qs(['void_only' => '1']) ?>">Voided only</a>
            <?php endif; ?>
          </div>
          <div>
            <label style="visibility:hidden;">Apply</label>
            <button type="submit" class="btn">Apply filters</button>
          </div>
        </form>
        <div class="records-table-wrap">
          <table class="records-table data-table">
            <thead>
              <tr>
                <th>Delivery date</th>
                <th>Photo</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <?php
                    $itemId = (int) $r['item_id'];
                    $batchId = (int) $r['batch_id'];
                    $fn = (string) $r['photo_filename'];
                    $st = (string) $r['item_status'];
                    $tr = $r['tracking_number'] !== null && trim((string) $r['tracking_number']) !== '' ? trim((string) $r['tracking_number']) : '';
                    $raw = $r['raw_qr_text'] !== null ? (string) $r['raw_qr_text'] : '';
                    $voided = $r['voided_at'] !== null && $r['voided_at'] !== '';
                    $dd = (string) $r['delivery_date'];
                    $imgUrl = '../assets/deliveries_documentation/' . rawurlencode($fn);
                    $dupCount = (int) ($r['dup_same_tracking'] ?? 0);
                    $isDup = !$voided && $tr !== '' && $dupCount > 1;
                    $missing = !$voided && $tr === '';
                    $trClasses = [];
                    if ($missing) {
                        $trClasses[] = 'row-missing';
                    }
                    if ($isDup) {
                        $trClasses[] = 'row-dup';
                    }
                    if ($voided) {
                        $trClasses[] = 'row-voided';
                    }
                    ?>
                <tr class="<?= htmlspecialchars(implode(' ', $trClasses), ENT_QUOTES, 'UTF-8') ?>"
                  data-item-id="<?= $itemId ?>"
                  data-batch-id="<?= $batchId ?>"
                  data-delivery-date="<?= htmlspecialchars($dd, ENT_QUOTES, 'UTF-8') ?>"
                  data-photo-url="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                  data-filename="<?= htmlspecialchars($fn, ENT_QUOTES, 'UTF-8') ?>"
                  data-tracking="<?= htmlspecialchars($tr, ENT_QUOTES, 'UTF-8') ?>"
                  data-status="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>"
                  data-raw="<?= htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') ?>"
                  data-voided="<?= $voided ? '1' : '0' ?>"
                  data-dup="<?= $isDup ? '1' : '0' ?>"
                >
                  <td><?= htmlspecialchars($dd, ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <img class="thumb" src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                  </td>
                  <td class="mono td-tracking"><?= $tr !== '' ? htmlspecialchars($tr, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                  <td>
                    <span class="pill"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($voided): ?>
                      <span class="pill" style="margin-left:0.25rem;border-color:#999;color:#555;">Voided</span>
                    <?php elseif ($isDup): ?>
                      <span class="pill dup-warn" style="margin-left:0.25rem;">Duplicate</span>
                    <?php endif; ?>
                  </td>
                  <td class="actions-cell">
                    <button type="button" class="icon-btn js-view" title="View details" aria-label="View"><?= $iconEye ?></button>
                    <?php if (!$voided): ?>
                      <button type="button" class="icon-btn js-edit" title="Edit tracking" aria-label="Edit"><?= $iconPen ?></button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($rows === [] && $err === null): ?>
          <p class="muted" style="margin:0.5rem 0 0;">No line items match these filters.</p>
        <?php endif; ?>
        <div class="pager">
          <span class="muted"><?= $total > 0 ? 'Showing ' . (int) (($page - 1) * $size + 1) . '–' . (int) min($page * $size, $total) . ' of ' . $total : 'No results' ?></span>
          <span>
            <?php if ($page > 1): ?>
              <a class="pager-link" data-pager="1" href="review.php?<?= $qs(['page' => $page - 1]) ?>">Previous</a>
            <?php else: ?>
              <span class="muted">Previous</span>
            <?php endif; ?>
            &nbsp;·&nbsp;
            <?php if ($page < $totalPages): ?>
              <a class="pager-link" data-pager="1" href="review.php?<?= $qs(['page' => $page + 1]) ?>">Next</a>
            <?php else: ?>
              <span class="muted">Next</span>
            <?php endif; ?>
          </span>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <div id="viewModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="viewModalTitle">
    <div class="modal-panel wide">
      <h2 id="viewModalTitle" style="margin:0 0 0.75rem;font-size:1.05rem;">Line item</h2>
      <div id="viewModalBody"></div>
      <div class="modal-nav-row">
        <button type="button" class="btn btn-ghost" id="viewPrevBtn">Previous</button>
        <button type="button" class="btn btn-ghost" id="viewNextBtn">Next</button>
        <button type="button" class="btn btn-ghost" id="viewModalClose" style="margin-left:auto;">Close</button>
      </div>
    </div>
  </div>

  <div id="editModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal-panel modal-edit-wide">
      <div class="modal-edit-grid">
        <div class="edit-side-photo">
          <img id="editSideImg" src="" alt="Delivery photo">
          <p class="muted" style="margin:0.5rem 0 0;font-size:0.8rem;">Photo reference for reading tracking from the label.</p>
        </div>
        <div>
          <h2 id="editModalTitle" style="margin:0 0 0.5rem;font-size:1.05rem;">Edit tracking</h2>
          <p class="muted" style="margin:0 0 0.75rem;font-size:0.85rem;">Item #<span id="editItemId"></span> · Batch #<span id="editBatchId"></span></p>
          <label for="editTrackingInput" style="display:block;font-size:0.8rem;color:var(--muted);margin-bottom:0.25rem;">Tracking number</label>
          <input type="text" id="editTrackingInput" style="width:100%;padding:0.5rem;border-radius:8px;border:1px solid var(--border);font-size:0.95rem;">
          <div class="modal-nav-row">
            <button type="button" class="btn btn-ghost" id="editPrevBtn">Previous</button>
            <button type="button" class="btn btn-ghost" id="editNextBtn">Next</button>
          </div>
          <div style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button type="button" class="btn" id="editSaveBtn">Save</button>
            <button type="button" class="btn btn-ghost btn-danger" id="editVoidBtn">Void row</button>
            <button type="button" class="btn btn-ghost" id="editModalClose">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="saveSuccess" class="success-overlay hidden" aria-hidden="true">
    <div class="success-modal">
      <div class="success-check"></div>
      <p style="margin:0 0 0.25rem;font-weight:600;">Saved</p>
      <p class="muted" style="margin:0;font-size:0.85rem;">Your change was recorded.</p>
    </div>
  </div>

  <div id="voidConfirmBackdrop" class="void-confirm-backdrop hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="voidConfirmTitle">
    <div class="void-confirm-modal">
      <h3 id="voidConfirmTitle">Void this delivery row?</h3>
      <p id="voidConfirmSummary" class="void-confirm-summary"></p>
      <div class="void-confirm-actions">
        <button type="button" class="btn-cancel-confirm" id="voidConfirmCancel">Cancel</button>
        <button type="button" class="btn-danger-confirm" id="voidConfirmYes">Yes, void row</button>
      </div>
    </div>
  </div>

  <script>
(function () {
  var api = '../api/delivery_resolve.php';
  var viewModal = document.getElementById('viewModal');
  var viewBody = document.getElementById('viewModalBody');
  var viewClose = document.getElementById('viewModalClose');
  var viewPrev = document.getElementById('viewPrevBtn');
  var viewNext = document.getElementById('viewNextBtn');
  var editModal = document.getElementById('editModal');
  var editSideImg = document.getElementById('editSideImg');
  var editItemId = document.getElementById('editItemId');
  var editBatchId = document.getElementById('editBatchId');
  var editInput = document.getElementById('editTrackingInput');
  var editSave = document.getElementById('editSaveBtn');
  var editVoid = document.getElementById('editVoidBtn');
  var editClose = document.getElementById('editModalClose');
  var editPrev = document.getElementById('editPrevBtn');
  var editNext = document.getElementById('editNextBtn');
  var saveSuccess = document.getElementById('saveSuccess');
  var voidConfirmBackdrop = document.getElementById('voidConfirmBackdrop');
  var voidConfirmSummary = document.getElementById('voidConfirmSummary');
  var voidConfirmCancel = document.getElementById('voidConfirmCancel');
  var voidConfirmYes = document.getElementById('voidConfirmYes');
  var currentEditId = 0;
  var currentViewTr = null;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function allTableRows() {
    return Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-item-id]'));
  }

  function editableRows() {
    return allTableRows().filter(function (tr) { return tr.getAttribute('data-voided') !== '1'; });
  }

  function rowFromBtn(btn) {
    return btn.closest('tr');
  }

  function fillViewFromTr(tr) {
    if (!tr || !viewBody) return;
    currentViewTr = tr;
    var url = tr.getAttribute('data-photo-url');
    var fn = tr.getAttribute('data-filename');
    var trk = tr.getAttribute('data-tracking');
    var st = tr.getAttribute('data-status');
    var raw = tr.getAttribute('data-raw');
    var voided = tr.getAttribute('data-voided') === '1';
    var bid = tr.getAttribute('data-batch-id');
    var iid = tr.getAttribute('data-item-id');
    var dd = tr.getAttribute('data-delivery-date');
    var html = '';
    if (url) {
      html += '<p style="margin:0 0 0.75rem;"><img class="preview" src="' + esc(url) + '" alt=""></p>';
      html += '<p style="margin:0 0 0.5rem;"><a class="btn" href="' + esc(url) + '" download="' + esc(fn) + '">Download image</a>';
      html += ' <a class="btn btn-ghost" href="' + esc(url) + '" target="_blank" rel="noopener">Open in new tab</a></p>';
    }
    if (voided) {
      html += '<p class="muted" style="margin:0 0 0.5rem;"><strong>Voided</strong> — image kept for audit.</p>';
    }
    html += '<dl>';
    html += '<dt>Delivery date</dt><dd>' + esc(dd || '') + '</dd>';
    html += '<dt>Batch</dt><dd class="mono">' + esc(bid) + '</dd>';
    html += '<dt>Item ID</dt><dd class="mono">' + esc(iid) + '</dd>';
    html += '<dt>Filename</dt><dd class="mono">' + esc(fn || '') + '</dd>';
    html += '<dt>Status</dt><dd>' + esc(st || '') + '</dd>';
    html += '<dt>Tracking</dt><dd class="mono">' + (trk ? esc(trk) : '—') + '</dd>';
    html += '<dt>Raw QR / payload</dt><dd class="mono">' + (raw ? esc(raw) : '—') + '</dd>';
    html += '</dl>';
    viewBody.innerHTML = html;
  }

  function openView(tr) {
    fillViewFromTr(tr);
    viewModal.classList.remove('hidden');
    viewModal.setAttribute('aria-hidden', 'false');
  }

  function stepView(delta) {
    var list = allTableRows();
    if (!currentViewTr || list.length < 1) return;
    var idx = list.indexOf(currentViewTr);
    if (idx < 0) return;
    var n = list[idx + delta];
    if (n) fillViewFromTr(n);
  }

  function fillEditFromTr(tr) {
    if (!tr || !editInput) return;
    currentEditId = parseInt(tr.getAttribute('data-item-id'), 10) || 0;
    editItemId.textContent = String(currentEditId);
    editBatchId.textContent = tr.getAttribute('data-batch-id') || '';
    editInput.value = tr.getAttribute('data-tracking') || '';
    var u = tr.getAttribute('data-photo-url');
    if (editSideImg) {
      editSideImg.src = u || '';
      editSideImg.alt = 'Delivery photo';
    }
  }

  function openEdit(tr) {
    fillEditFromTr(tr);
    editModal.classList.remove('hidden');
    editModal.setAttribute('aria-hidden', 'false');
    editInput.focus();
  }

  function stepEdit(delta) {
    var list = editableRows();
    if (currentEditId < 1 || list.length < 1) return;
    var idx = -1;
    for (var i = 0; i < list.length; i++) {
      if (parseInt(list[i].getAttribute('data-item-id'), 10) === currentEditId) {
        idx = i;
        break;
      }
    }
    if (idx < 0) return;
    var n = list[idx + delta];
    if (n) fillEditFromTr(n);
  }

  document.querySelectorAll('.js-view').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openView(rowFromBtn(btn));
    });
  });

  document.querySelectorAll('.js-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openEdit(rowFromBtn(btn));
    });
  });

  if (viewPrev) viewPrev.addEventListener('click', function () { stepView(-1); });
  if (viewNext) viewNext.addEventListener('click', function () { stepView(1); });
  if (editPrev) editPrev.addEventListener('click', function () { stepEdit(-1); });
  if (editNext) editNext.addEventListener('click', function () { stepEdit(1); });

  function closeView() {
    if (viewModal) {
      viewModal.classList.add('hidden');
      viewModal.setAttribute('aria-hidden', 'true');
    }
    currentViewTr = null;
  }
  function closeEdit() {
    if (editModal) {
      editModal.classList.add('hidden');
      editModal.setAttribute('aria-hidden', 'true');
    }
    currentEditId = 0;
    if (editSideImg) editSideImg.src = '';
  }

  if (viewClose) viewClose.addEventListener('click', closeView);
  if (viewModal) viewModal.addEventListener('click', function (e) { if (e.target === viewModal) closeView(); });
  if (editClose) editClose.addEventListener('click', closeEdit);
  if (editModal) editModal.addEventListener('click', function (e) { if (e.target === editModal) closeEdit(); });

  function showSaveSuccessThenReload() {
    if (!saveSuccess) {
      window.location.reload();
      return;
    }
    saveSuccess.classList.remove('hidden');
    saveSuccess.setAttribute('aria-hidden', 'false');
    setTimeout(function () {
      window.location.reload();
    }, 1100);
  }

  if (editSave) {
    editSave.addEventListener('click', function () {
      if (currentEditId < 1) return;
      var v = (editInput.value || '').trim();
      if (!v) { alert('Enter a tracking number'); return; }
      fetch(api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'resolve', item_id: currentEditId, tracking_number: v })
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (j.ok) {
          closeEdit();
          showSaveSuccessThenReload();
        } else {
          alert(j.error || 'Failed');
        }
      }).catch(function () { alert('Network error'); });
    });
  }
  function closeVoidConfirm() {
    if (!voidConfirmBackdrop) return;
    voidConfirmBackdrop.classList.add('hidden');
    voidConfirmBackdrop.setAttribute('aria-hidden', 'true');
    if (voidConfirmYes) {
      voidConfirmYes.disabled = false;
      voidConfirmYes.textContent = 'Yes, void row';
    }
  }

  function openVoidConfirm() {
    if (currentEditId < 1 || !voidConfirmBackdrop) return;
    var trk = editInput ? (editInput.value || '').trim() : '';
  var batch = editBatchId ? editBatchId.textContent : '';
    if (voidConfirmSummary) {
      voidConfirmSummary.innerHTML =
        'Item #' + esc(String(currentEditId)) +
        (batch ? ' · Batch #' + esc(batch) : '') +
        '<br><br>Tracking: <strong>' + esc(trk || '—') + '</strong><br><br>' +
        'The row stays in the list for audit. The photo file is <strong>not</strong> deleted from disk.';
    }
    voidConfirmBackdrop.classList.remove('hidden');
    voidConfirmBackdrop.setAttribute('aria-hidden', 'false');
    if (voidConfirmYes) voidConfirmYes.focus();
  }

  function doVoidRow() {
    if (currentEditId < 1) return;
    if (voidConfirmYes) {
      voidConfirmYes.disabled = true;
      voidConfirmYes.textContent = 'Voiding…';
    }
    fetch(api, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action: 'void', item_id: currentEditId })
    }).then(function (r) { return r.json(); }).then(function (j) {
      if (j.ok) {
        closeVoidConfirm();
        closeEdit();
        showSaveSuccessThenReload();
      } else {
        alert(j.error || 'Failed');
        closeVoidConfirm();
      }
    }).catch(function () {
      alert('Network error');
      closeVoidConfirm();
    });
  }

  if (editVoid) editVoid.addEventListener('click', openVoidConfirm);
  if (voidConfirmCancel) voidConfirmCancel.addEventListener('click', closeVoidConfirm);
  if (voidConfirmYes) voidConfirmYes.addEventListener('click', doVoidRow);
  if (voidConfirmBackdrop) {
    voidConfirmBackdrop.addEventListener('click', function (e) {
      if (e.target === voidConfirmBackdrop) closeVoidConfirm();
    });
  }

  var mBtn = document.getElementById('mobileMenuBtn');
  var mPanel = document.getElementById('mobileMenuPanel');
  if (mBtn && mPanel) {
    var setOpen = function (open) {
      mPanel.classList.toggle('open', !!open);
      mPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
    };
    mBtn.addEventListener('click', function () {
      setOpen(!mPanel.classList.contains('open'));
    });
    mPanel.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (viewModal && !viewModal.classList.contains('hidden')) closeView();
    else if (editModal && !editModal.classList.contains('hidden')) closeEdit();
  });

  document.querySelectorAll('a[href$=".php"], a[href*=".php?"]').forEach(function (a) {
    if (a.getAttribute('data-pager')) {
      a.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        var href = a.getAttribute('href');
        var card = document.querySelector('.table-card');
        if (card) {
          card.style.animation = 'none';
          void card.offsetWidth;
          card.style.animation = '';
        }
        document.body.classList.add('page-exit');
        setTimeout(function () { window.location.href = href; }, 200);
      });
      return;
    }
    a.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#') return;
      e.preventDefault();
      document.body.classList.add('page-exit');
      setTimeout(function () { window.location.href = href; }, 180);
    });
  });
})();
  </script>
</body>
</html>
