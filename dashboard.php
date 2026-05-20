<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/labels.php';
require_once __DIR__ . '/includes/auth.php';

rts_require_roles([RTS_ROLE_ADMIN], 'login.php');

$pageTitle = 'RTS dashboard';

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><p>Database connection failed. Check <code>config/config.php</code>.</p></body></html>';
    exit;
}

require_once __DIR__ . '/includes/deliveries_review_nav.php';
require_once __DIR__ . '/includes/admin_nav.php';
require_once __DIR__ . '/includes/sp_sort_service.php';
require_once __DIR__ . '/includes/rts_products.php';
$deliveryNavBadge = rts_delivery_review_nav_badge_html($pdo);

$totalCases = (int) $pdo->query('SELECT COUNT(*) FROM rts_cases')->fetchColumn();
$casesLast30 = (int) $pdo->query(
    'SELECT COUNT(*) FROM rts_cases WHERE date_opened >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'
)->fetchColumn();

$stmtMonth = $pdo->query(
    'SELECT COUNT(*) FROM rts_cases WHERE date_opened >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND date_opened < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), "%Y-%m-01")'
);
$casesThisMonth = (int) $stmtMonth->fetchColumn();

$vipCount = (int) $pdo->query("SELECT COUNT(*) FROM rts_cases WHERE shop = 'vip'")->fetchColumn();
$vipPct = $totalCases > 0 ? round(100 * $vipCount / $totalCases, 1) : 0.0;

$byShop = $pdo->query(
    'SELECT shop, COUNT(*) AS cnt FROM rts_cases GROUP BY shop ORDER BY cnt DESC'
)->fetchAll(PDO::FETCH_KEY_PAIR);
if ($byShop === false) {
    $byShop = [];
}

$byCourier = $pdo->query(
    'SELECT courier_final, COUNT(*) AS cnt FROM rts_cases GROUP BY courier_final ORDER BY cnt DESC'
)->fetchAll(PDO::FETCH_KEY_PAIR);
if ($byCourier === false) {
    $byCourier = [];
}

if (rts_line_items_schema_v2($pdo)) {
    $lineAgg = $pdo->query(
        "SELECT
           SUM(CASE WHEN product_type IN ('honey','honey_with_other') THEN 1 ELSE 0 END) AS honey_lines,
           SUM(CASE WHEN product_type NOT IN ('honey','honey_with_other') THEN 1 ELSE 0 END) AS other_lines,
           COALESCE(SUM(CASE WHEN product_type IN ('honey','honey_with_other') THEN quantity ELSE 0 END), 0) AS honey_qty
         FROM rts_line_items"
    )->fetch() ?: ['honey_lines' => 0, 'other_lines' => 0, 'honey_qty' => 0];

    $nonHoneyTop = $pdo->query(
        "SELECT product_type, SUM(quantity) AS qty
         FROM rts_line_items WHERE product_type NOT IN ('honey','honey_with_other')
         GROUP BY product_type ORDER BY qty DESC LIMIT 8"
    )->fetchAll();

    $honeyByVariant = $pdo->query(
        "SELECT honey_variant AS variant, SUM(quantity) AS qty
         FROM rts_line_items WHERE product_type IN ('honey','honey_with_other') AND honey_variant IS NOT NULL
         GROUP BY honey_variant ORDER BY qty DESC"
    )->fetchAll();
} else {
    $lineAgg = $pdo->query(
        "SELECT
           SUM(CASE WHEN kind = 'honey' THEN 1 ELSE 0 END) AS honey_lines,
           SUM(CASE WHEN kind IS NULL OR kind != 'honey' THEN 1 ELSE 0 END) AS other_lines,
           COALESCE(SUM(CASE WHEN kind = 'honey' THEN quantity ELSE 0 END), 0) AS honey_qty
         FROM rts_line_items"
    )->fetch() ?: ['honey_lines' => 0, 'other_lines' => 0, 'honey_qty' => 0];

    $nonHoneyTop = $pdo->query(
        "SELECT non_honey_product AS product_type, SUM(quantity) AS qty
         FROM rts_line_items
         WHERE kind != 'honey' AND non_honey_product IS NOT NULL AND non_honey_product != ''
         GROUP BY non_honey_product ORDER BY qty DESC LIMIT 8"
    )->fetchAll();

    $honeyByVariant = $pdo->query(
        "SELECT variant AS variant, SUM(quantity) AS qty
         FROM rts_line_items
         WHERE kind = 'honey' AND variant IS NOT NULL AND variant != ''
         GROUP BY variant ORDER BY qty DESC"
    )->fetchAll();
}

$timeline = $pdo->query(
    'SELECT DATE_FORMAT(date_opened, "%Y-%m-%d") AS d, COUNT(*) AS cnt
     FROM rts_cases
     WHERE date_opened >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY d ORDER BY d ASC'
)->fetchAll();

$recent = $pdo->query(
    'SELECT tracking_number, shop, courier_final, date_opened
     FROM rts_cases ORDER BY created_at DESC LIMIT 10'
)->fetchAll();

$damageByReason = $pdo->query(
    "SELECT damaged_reason, COUNT(*) AS cnt FROM rts_cases
     WHERE damaged_reason IS NOT NULL AND voided_at IS NULL
     GROUP BY damaged_reason ORDER BY cnt DESC"
)->fetchAll();
$totalDamaged = (int) $pdo->query(
    "SELECT COUNT(*) FROM rts_cases WHERE damaged_reason IS NOT NULL AND voided_at IS NULL"
)->fetchColumn();
$totalNotDamaged = max(0, $totalCases - $totalDamaged);

$spSortReady = sp_sort_tables_exist($pdo);
$deliveredByCourier = [];
$deliveredByShop = [];
$spSortCompletedBatches = 0;
if ($spSortReady) {
    $deliveredByCourier = sp_sort_courier_counts($pdo);
    $deliveredByShop = sp_sort_shop_counts($pdo);
    $spSortCompletedBatches = (int) $pdo->query(
        "SELECT COUNT(*) FROM sp_sort_batches WHERE status = 'completed'"
    )->fetchColumn();
}

$shopOrder = ['shopee', 'lazada', 'tiktok', 'vip'];
$shopColors = [
    'shopee' => '#d73333',
    'lazada' => '#1f6feb',
    'tiktok' => '#111111',
    'vip' => '#2e9e41',
];
$shopTotal = array_sum(array_map('intval', $byShop));
$shopSegments = [];
foreach ($shopOrder as $shopCode) {
    $cnt = (int) ($byShop[$shopCode] ?? 0);
    if ($cnt < 1 || $shopTotal < 1) {
        continue;
    }
    $shopSegments[] = [
        'label' => rts_shop_label($shopCode),
        'count' => $cnt,
        'pct' => round(($cnt / $shopTotal) * 100, 2),
        'color' => $shopColors[$shopCode],
    ];
}

$courierOrder = ['spx', 'jnt', 'flash', 'unknown'];
$courierColors = [
    'spx' => '#c62828',
    'jnt' => '#e53935',
    'flash' => '#f4b400',
    'unknown' => '#9e9e9e',
];
$courierTotal = array_sum(array_map('intval', $byCourier));
$courierSegments = [];
foreach ($courierOrder as $courierCode) {
    $cnt = (int) ($byCourier[$courierCode] ?? 0);
    if ($cnt < 1 || $courierTotal < 1) {
        continue;
    }
    $courierSegments[] = [
        'label' => rts_courier_label($courierCode),
        'count' => $cnt,
        'pct' => round(($cnt / $courierTotal) * 100, 2),
        'color' => $courierColors[$courierCode],
    ];
}

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
      --muted:#7e6a4b; --accent:#d99200; --vip:#b5831f; --warn:#f5a623;
    }
    * { box-sizing: border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); line-height:1.45; display:flex; min-height:100vh; opacity:1; transition:opacity .22s ease; }
    body.page-enter { animation: pageIn .28s ease both; }
    body.page-exit { opacity:0; }
    .sidebar {
      width: 220px;
      padding: 1.25rem 1rem;
      border-right: 1px solid var(--border);
      background: #fff5df;
      display:flex;
      flex-direction:column;
      gap:1.25rem;
      position: sticky;
      top: 0;
      align-self: flex-start;
      height: 100vh;
      overflow-y: auto;
    }
    .sidebar h1 {
      margin:0;
      font-size:1.05rem;
      font-weight:650;
    }
    .nav-links a {
      display:block;
      padding:0.4rem 0.1rem;
      color:var(--muted);
      text-decoration:none;
      font-size:0.9rem;
    }
    .nav-links a.active {
      color:var(--accent);
      font-weight:600;
    }
    .nav-links a:hover { color:#4a381d; }
    main { flex:1; max-width:1120px; margin:0 auto; padding:1rem 1.25rem 2.5rem; }
    .grid { display:grid; gap:1rem; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); }
    .stat {
      background:var(--panel); border:1px solid var(--border); border-radius:12px;
      padding:1rem 1.1rem;
    }
    .stat strong { display:block; font-size:1.65rem; font-weight:650; letter-spacing:-0.02em; margin-top:0.15rem; }
    .stat span { font-size:0.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.04em; }
    .stat.vip strong { color:var(--vip); }
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem; }
    @media (max-width: 860px) { .two-col { grid-template-columns:1fr; } }
    .card {
      background:var(--panel); border:1px solid var(--border); border-radius:12px;
      padding:1rem 1.15rem;
    }
    .card h2 { margin:0 0 0.75rem; font-size:1rem; font-weight:650; }
    table { width:100%; border-collapse:collapse; font-size:0.9rem; }
    th, td { text-align:left; padding:0.45rem 0.35rem; border-bottom:1px solid var(--border); }
    th { color:var(--muted); font-size:0.76rem; text-transform:uppercase; letter-spacing:0.03em; font-weight:600; }
    .stacked-bar {
      display:flex;
      width:100%;
      height:16px;
      border-radius:999px;
      overflow:hidden;
      background:#f6ecdb;
      border:1px solid var(--border);
      margin-top:0.5rem;
    }
    .stacked-bar .seg {
      height:100%;
      transition: width .35s ease;
    }
    .legend {
      display:flex;
      flex-wrap:wrap;
      gap:0.45rem 0.9rem;
      margin-top:0.65rem;
      font-size:0.82rem;
      color:var(--muted);
    }
    .legend .item {
      display:flex;
      align-items:center;
      gap:0.35rem;
    }
    .legend .dot {
      width:10px;
      height:10px;
      border-radius:50%;
      display:inline-block;
      border:1px solid rgba(0,0,0,0.12);
    }
    .timeline { display:flex; align-items:flex-end; gap:4px; height:140px; margin-top:0.5rem; }
    .timeline .col { flex:1; min-width:0; display:flex; flex-direction:column; justify-content:flex-end; }
    .timeline .pill { background:linear-gradient(to top,#3d9cf566,#3d9cf5bb); border-radius:4px 4px 0 0; min-height:4px; }
    .timeline .lab { font-size:0.65rem; color:var(--muted); text-align:center; margin-top:0.35rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .mono { font-family:ui-monospace,Consolas,monospace; font-size:0.85rem; }
    .meta { font-size:0.85rem; color:var(--muted); margin:0 0 1rem; }
    .mobile-topbar {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 56px;
      z-index: 90;
      background: #fff5df;
      border-bottom: 1px solid var(--border);
      padding: 0 0.85rem;
      align-items: center;
      gap: 0.75rem;
    }
    .mobile-menu-btn {
      background: #fff7ea;
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: 10px;
      padding: 0.45rem 0.6rem;
      cursor: pointer;
      font-size: 1.05rem;
      line-height: 1;
    }
    .mobile-menu-panel {
      display: block;
      position: fixed;
      z-index: 95;
      top: 56px;
      left: 0;
      width: 240px;
      bottom: 0;
      background: #fff5df;
      border-right: 1px solid var(--border);
      padding: 1rem 0.9rem;
      overflow-y: auto;
      transform: translateX(-105%);
      opacity: 0;
      pointer-events: none;
      transition: transform .22s ease, opacity .22s ease;
    }
    .mobile-menu-panel.open { transform: translateX(0); opacity:1; pointer-events:auto; }
    @keyframes pageIn {
      from { opacity:0; }
      to { opacity:1; }
    }
    @media (max-width: 720px) {
      .sidebar { display: none !important; }
      .mobile-topbar { display: flex; }
      main { padding-top: 80px; }
    }
  </style>
</head>
<body class="page-enter">
  <div class="mobile-topbar">
    <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">☰</button>
    <img src="assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:28px;width:auto;border-radius:6px;" onerror="this.style.display='none'">
    <div style="font-weight:700;">RTS system</div>
  </div>
  <div id="mobileMenuPanel" class="mobile-menu-panel" aria-hidden="true">
    <nav class="nav-links">
      <?php rts_admin_nav_links('dashboard', '', $deliveryNavBadge); ?>
    </nav>
  </div>
  <aside class="sidebar">
    <div>
      <img src="assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:42px;width:auto;max-width:100%;border-radius:8px;margin-bottom:0.5rem;" onerror="this.style.display='none'">
      <h1>RTS system</h1>
      <p class="meta" style="margin:0.25rem 0 0;font-size:0.78rem;">Returns tracking &amp; reporting</p>
    </div>
    <nav class="nav-links">
      <?php rts_admin_nav_links('dashboard', '', $deliveryNavBadge); ?>
    </nav>
  </aside>
  <main>
    <p class="meta">Snapshot of parcels recorded and products returned — based on open / record date and line items.</p>

    <div class="grid">
      <div class="stat"><span>Total parcels (RTS)</span><strong><?= (int) $totalCases ?></strong></div>
      <div class="stat"><span>Last 30 days</span><strong><?= (int) $casesLast30 ?></strong></div>
      <div class="stat"><span>This calendar month</span><strong><?= (int) $casesThisMonth ?></strong></div>
      <div class="stat vip"><span>VIP parcels</span><strong><?= (int) $vipCount ?></strong><small style="color:var(--muted);display:block;margin-top:0.35rem;"><?= $vipPct ?>% of total</small></div>
    </div>

    <div class="two-col">
      <div class="card">
        <h2>Returns by shop</h2>
        <?php if ($shopSegments === []): ?>
          <p style="margin:0;color:var(--muted);">No data yet.</p>
        <?php else: ?>
          <div class="stacked-bar" aria-label="Returns by shop stacked percentage">
            <?php foreach ($shopSegments as $seg): ?>
              <span class="seg" style="width:<?= htmlspecialchars((string) $seg['pct'], ENT_QUOTES, 'UTF-8') ?>%;background:<?= htmlspecialchars($seg['color'], ENT_QUOTES, 'UTF-8') ?>;" title="<?= htmlspecialchars($seg['label'] . ' — ' . $seg['pct'] . '%', ENT_QUOTES, 'UTF-8') ?>"></span>
            <?php endforeach; ?>
          </div>
          <div class="legend">
            <?php foreach ($shopSegments as $seg): ?>
              <span class="item"><span class="dot" style="background:<?= htmlspecialchars($seg['color'], ENT_QUOTES, 'UTF-8') ?>"></span><?= htmlspecialchars($seg['label'] . ' ' . $seg['pct'] . '% (' . $seg['count'] . ')', ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Returns by courier</h2>
        <?php if ($courierSegments === []): ?>
          <p style="margin:0;color:var(--muted);">No data yet.</p>
        <?php else: ?>
          <div class="stacked-bar" aria-label="Returns by courier stacked percentage">
            <?php foreach ($courierSegments as $seg): ?>
              <span class="seg" style="width:<?= htmlspecialchars((string) $seg['pct'], ENT_QUOTES, 'UTF-8') ?>%;background:<?= htmlspecialchars($seg['color'], ENT_QUOTES, 'UTF-8') ?>;" title="<?= htmlspecialchars($seg['label'] . ' — ' . $seg['pct'] . '%', ENT_QUOTES, 'UTF-8') ?>"></span>
            <?php endforeach; ?>
          </div>
          <div class="legend">
            <?php foreach ($courierSegments as $seg): ?>
              <span class="item"><span class="dot" style="background:<?= htmlspecialchars($seg['color'], ENT_QUOTES, 'UTF-8') ?>"></span><?= htmlspecialchars($seg['label'] . ' ' . $seg['pct'] . '% (' . $seg['count'] . ')', ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="two-col">
      <div class="card">
        <h2>Damages report (RTS)</h2>
        <?php if ($totalDamaged < 1): ?>
          <p style="margin:0;color:var(--muted);">No damaged parcels recorded.</p>
        <?php else: ?>
          <table>
            <tbody>
              <tr><th>Total damaged</th><td><strong><?= (int) $totalDamaged ?></strong></td></tr>
              <tr><th>Not damaged</th><td><?= (int) $totalNotDamaged ?></td></tr>
            </tbody>
          </table>
          <table style="margin-top:0.75rem;">
            <thead><tr><th>Reason</th><th>Count</th></tr></thead>
            <tbody>
              <?php
              $dmgLabels = [
                  'leak' => 'Leak',
                  'item_replaced' => 'Item replaced',
                  'packaging_replaced' => 'Packaging replaced',
              ];
              foreach ($damageByReason as $dr):
                  $reason = (string) $dr['damaged_reason'];
                  ?>
              <tr>
                <td><?= htmlspecialchars($dmgLabels[$reason] ?? $reason, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $dr['cnt'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p style="margin:0.75rem 0 0;font-size:0.82rem;"><a href="records.php?damage=all_damaged" style="color:var(--accent);">View damaged records →</a></p>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Delivered items (SP Sort)</h2>
        <?php if (!$spSortReady): ?>
          <p style="margin:0;color:var(--muted);">Run <code>sql/migration_sp_sort.sql</code> to enable delivered-item reporting.</p>
        <?php elseif ($spSortCompletedBatches < 1): ?>
          <p style="margin:0;color:var(--muted);">No completed SP Sort batches yet. <a href="sp_sort/index.php" style="color:var(--accent);">Import Excel →</a></p>
        <?php else: ?>
          <p style="margin:0 0 0.5rem;font-size:0.85rem;color:var(--muted);"><?= (int) $spSortCompletedBatches ?> completed batch(es)</p>
          <table>
            <thead><tr><th>Courier</th><th>Rows</th></tr></thead>
            <tbody>
              <?php foreach (['spx', 'jnt', 'flash', 'unknown'] as $cc):
                  $cnt = (int) ($deliveredByCourier[$cc] ?? 0);
                  if ($cnt < 1) {
                      continue;
                  }
                  $rowStyle = $cc === 'flash' ? 'background:#fff9c4' : ($cc === 'spx' ? 'background:#ffe0b2' : '');
                  ?>
              <tr<?= $rowStyle !== '' ? ' style="' . htmlspecialchars($rowStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                <td><?= htmlspecialchars(rts_courier_label($cc), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $cnt ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p style="margin:0.65rem 0 0.35rem;color:var(--muted);font-size:0.8rem;">By shop</p>
          <table>
            <thead><tr><th>Shop</th><th>Rows</th></tr></thead>
            <tbody>
              <?php foreach (['shopee', 'lazada', 'tiktok', 'unknown'] as $ss):
                  $cnt = (int) ($deliveredByShop[$ss] ?? 0);
                  if ($cnt < 1) {
                      continue;
                  }
                  ?>
              <tr>
                <td><?= htmlspecialchars(rts_shop_label($ss), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $cnt ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p style="margin:0.75rem 0 0;font-size:0.82rem;"><a href="sp_sort/batches.php" style="color:var(--accent);">SP Sort history →</a></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="two-col">
      <div class="card">
        <h2>Line items (totals)</h2>
        <table>
          <tbody>
            <tr><th>Honey lines</th><td><?= (int) $lineAgg['honey_lines'] ?></td></tr>
            <tr><th>Total honey qty (units)</th><td><?= htmlspecialchars((string) $lineAgg['honey_qty'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Non‑honey lines</th><td><?= (int) $lineAgg['other_lines'] ?></td></tr>
          </tbody>
        </table>
        <?php if ($honeyByVariant !== []): ?>
          <p style="margin:0.75rem 0 0.35rem;color:var(--muted);font-size:0.8rem;">Honey by variant (qty)</p>
          <table>
            <thead><tr><th>Variant</th><th>Qty</th></tr></thead>
            <tbody>
              <?php foreach ($honeyByVariant as $r): ?>
              <tr><td><?= htmlspecialchars(ucfirst((string) $r['variant']), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $r['qty'], ENT_QUOTES, 'UTF-8') ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Non‑honey products (top by qty)</h2>
        <?php if ($nonHoneyTop === []): ?>
          <p style="margin:0;color:var(--muted);">No non‑honey lines yet.</p>
        <?php else: ?>
          <table>
            <thead><tr><th>Product</th><th>Qty</th></tr></thead>
            <tbody>
        <?php
              $ptLabels = [
                  'pouch_pt' => 'Pouch PT',
                  'pouch_pt_with_honey' => 'Pouch PT w/ Honey',
                  'pouch_brc' => 'Pouch BRC',
                  'jar_cacao' => 'Jar Cacao',
                  'jar_cacao_with_honey' => 'Jar Cacao w/ Honey',
                  'jar_honey_comb' => 'Jar Honey Comb',
                  'paragis_tea' => 'Paragis tea',
                  'black_rice_tea' => 'Black rice tea',
                  'cacao' => 'Cacao',
                  'honey_comb' => 'Honey comb',
                  'pito_pito' => 'Pito-pito',
              ];
              foreach ($nonHoneyTop as $r):
                  $slug = (string) $r['product_type'];
                  $name = $ptLabels[$slug] ?? ucwords(str_replace('_', ' ', $slug));
                  ?>
              <tr>
                <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $r['qty'], ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:1rem;">
      <h2>Open / record dates (last 14 days)</h2>
      <?php if ($timeline === []): ?>
        <p style="margin:0;color:var(--muted);">No parcels in this window.</p>
      <?php else: ?>
        <?php $tmax = (int) max(array_column($timeline, 'cnt')); ?>
        <div class="timeline">
          <?php foreach ($timeline as $row):
              $h = $tmax > 0 ? max(16, round(130 * ((int) $row['cnt'] / $tmax))) : 16;
              $dshort = substr((string) $row['d'], 8, 2) . '.' . substr((string) $row['d'], 5, 2);
              ?>
          <div class="col" title="<?= htmlspecialchars((string) $row['d'] . ': ' . (int) $row['cnt'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="pill" style="height:<?= (int) $h ?>px"></div>
            <div class="lab"><?= htmlspecialchars($dshort, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card" style="margin-top:1rem;">
      <h2>Recent RTS</h2>
      <?php if ($recent === []): ?>
        <p style="margin:0;color:var(--muted);">Nothing recorded yet. <a href="record.php" style="color:var(--accent);">Record RTS</a>.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr><th>Tracking</th><th>Shop</th><th>Courier</th><th>Opened</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td class="mono"><a href="records.php?q=<?= urlencode((string) $r['tracking_number']) ?>" style="color:var(--accent);"><?= htmlspecialchars((string) $r['tracking_number'], ENT_QUOTES, 'UTF-8') ?></a></td>
              <td><?= htmlspecialchars(rts_shop_label((string) $r['shop']), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars(rts_courier_label((string) $r['courier_final']), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $r['date_opened'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>
  <script>
  (function () {
    var btn = document.getElementById('mobileMenuBtn');
    var panel = document.getElementById('mobileMenuPanel');
    if (!btn || !panel) return;

    function setOpen(open) {
      panel.classList.toggle('open', !!open);
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    btn.addEventListener('click', function () {
      setOpen(!panel.classList.contains('open'));
    });

    var links = panel.querySelectorAll('a');
    links.forEach(function (a) {
      a.addEventListener('click', function () { setOpen(false); });
    });

    document.querySelectorAll('a[href$=".php"], a[href*=".php?"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var href = a.getAttribute('href');
        if (!href) return;
        e.preventDefault();
        document.body.classList.add('page-exit');
        setTimeout(function () { window.location.href = href; }, 180);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  })();
  </script>
</body>
</html>
