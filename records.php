<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/labels.php';
require_once __DIR__ . '/includes/rts_products.php';
require_once __DIR__ . '/includes/rts_claim_status.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rts_attachments.php';
require_once __DIR__ . '/includes/rts_storage.php';

rts_require_roles([RTS_ROLE_ADMIN], 'login.php');

$pageTitle = 'RTS records';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$shop = isset($_GET['shop']) ? trim((string) $_GET['shop']) : '';
$courier = isset($_GET['courier']) ? trim((string) $_GET['courier']) : '';
$vipOnly = isset($_GET['vip_only']) && $_GET['vip_only'] === '1';
$dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
$delFrom = isset($_GET['del_from']) ? trim((string) $_GET['del_from']) : '';
$delTo = isset($_GET['del_to']) ? trim((string) $_GET['del_to']) : '';
$damage = isset($_GET['damage']) ? trim((string) $_GET['damage']) : '';
$voidOnly = isset($_GET['void_only']) && $_GET['void_only'] === '1';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowedShop = ['', 'shopee', 'lazada', 'tiktok', 'vip'];
$allowedCourier = ['', 'spx', 'jnt', 'flash', 'unknown'];
if (!in_array($shop, $allowedShop, true)) {
    $shop = '';
}
if (!in_array($courier, $allowedCourier, true)) {
    $courier = '';
}
$allowedDamage = ['', 'all_damaged', 'not_damaged', 'leak', 'item_replaced', 'packaging_replaced'];
if (!in_array($damage, $allowedDamage, true)) {
    $damage = '';
}

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><p>Database connection failed. Check <code>config/config.php</code> and import <code>sql/schema.sql</code>.</p></body></html>';
    exit;
}

require_once __DIR__ . '/includes/deliveries_review_nav.php';
require_once __DIR__ . '/includes/admin_nav.php';
$deliveryNavBadge = rts_delivery_review_nav_badge_html($pdo);

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = 'c.tracking_number LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($vipOnly) {
    $where[] = "c.shop = 'vip'";
} elseif ($shop !== '') {
    $where[] = 'c.shop = ?';
    $params[] = $shop;
}
if ($courier !== '') {
    $where[] = 'c.courier_final = ?';
    $params[] = $courier;
}
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'c.date_opened >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'c.date_opened <= ?';
    $params[] = $dateTo;
}
if ($delFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delFrom)) {
    $where[] = 'c.date_delivered >= ?';
    $params[] = $delFrom;
}
if ($delTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delTo)) {
    $where[] = 'c.date_delivered <= ?';
    $params[] = $delTo;
}
if ($damage === 'all_damaged') {
    $where[] = 'c.damaged_reason IS NOT NULL';
} elseif ($damage === 'not_damaged') {
    $where[] = 'c.damaged_reason IS NULL';
} elseif (in_array($damage, ['leak', 'item_replaced', 'packaging_replaced'], true)) {
    $where[] = 'c.damaged_reason = ?';
    $params[] = $damage;
}
if ($voidOnly) {
    $where[] = 'c.voided_at IS NOT NULL';
}

$whereSql = implode(' AND ', $where);

if ($voidOnly) {
    $orderSql = 'ORDER BY c.voided_at DESC, c.id DESC';
} else {
    $orderSql = 'ORDER BY (c.voided_at IS NOT NULL) ASC, (c.damaged_reason IS NOT NULL) DESC, c.date_opened DESC, c.id DESC';
}

$countSql = 'SELECT COUNT(*) FROM rts_cases c WHERE ' . $whereSql;
$cntSt = $pdo->prepare($countSql);
$cntSt->execute($params);
$totalCount = (int) $cntSt->fetchColumn();

$sql = '
  SELECT c.id, c.tracking_number, c.courier_detected, c.courier_final, c.shop, c.vip_subsource,
         c.date_delivered, c.date_opened, c.documentation_photo, c.documentation_attachments,
         c.damaged_reason, c.damaged_tag, c.claim_status, c.claim_status_note,
         c.voided_at, c.created_at
  FROM rts_cases c
  WHERE ' . $whereSql . '
  ' . $orderSql . '
  LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset . '
';
$paramsPaged = $params;
$st = $pdo->prepare($sql);
$st->execute($paramsPaged);
$cases = $st->fetchAll();

$totalPages = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
$start = $totalCount === 0 ? 0 : $offset + 1;
$end = $totalCount === 0 ? 0 : min($offset + $perPage, $totalCount);

function records_filter_query(array $overrides = []): array
{
    global $q, $shop, $courier, $vipOnly, $dateFrom, $dateTo, $delFrom, $delTo, $damage, $voidOnly;
    $base = [
        'q' => $q,
        'shop' => $shop,
        'courier' => $courier,
        'vip_only' => $vipOnly ? '1' : '',
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'del_from' => $delFrom,
        'del_to' => $delTo,
        'damage' => $damage,
        'void_only' => $voidOnly ? '1' : '',
    ];
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) {
            unset($base[$k]);
        } else {
            $base[$k] = $v;
        }
    }

    return array_filter($base, static fn ($v) => $v !== '' && $v !== null);
}

function records_page_url(int $p): string
{
    $q = records_filter_query(['page' => (string) $p]);

    return 'records.php?' . http_build_query($q);
}

function records_url(array $overrides = []): string
{
    $q = records_filter_query($overrides);
    $q['page'] = '1';

    return 'records.php?' . http_build_query($q);
}

$caseIds = array_map(static fn ($r) => (int) $r['id'], $cases);
$linesByCase = [];
if ($caseIds !== []) {
    $placeholders = implode(',', array_fill(0, count($caseIds), '?'));
    $lst = $pdo->prepare(
        "SELECT case_id, product_type, honey_size, honey_variant, combo_product, combo_quantity, combo_honey_size, combo_honey_variant, quantity, is_bonus
         FROM rts_line_items WHERE case_id IN ($placeholders) ORDER BY id ASC"
    );
    $lst->execute($caseIds);
    while ($L = $lst->fetch()) {
        $cid = (int) $L['case_id'];
        if (!isset($linesByCase[$cid])) {
            $linesByCase[$cid] = [];
        }
        $linesByCase[$cid][] = $L;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#fffdf6; --panel:#ffffff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); line-height:1.45; display:flex; min-height:100vh; opacity:1; transition:opacity .22s ease; overflow-x:hidden; }
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
    main { flex:1; min-width:0; max-width:1100px; width:100%; margin:0 auto; padding:1rem 1.25rem 2rem; overflow-x:hidden; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:1rem 1.1rem; margin-bottom:1rem; }
    label { display:block; font-size:0.78rem; color:var(--muted); margin-bottom:0.2rem; }
    select, input[type="text"], input[type="date"] { width:100%; padding:0.45rem 0.55rem; border-radius:8px; border:1px solid var(--border); background:#fffdfa; color:var(--text); font-size:0.92rem; }
    .filters { display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:0.65rem 0.85rem; align-items:end; }
    .filters .full { grid-column: 1 / -1; }
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:0.5rem 1rem; border-radius:8px; border:1px solid var(--border); background:var(--accent); color:#061018; font-weight:600; cursor:pointer; font-size:0.92rem; border:none; }
    .btn-secondary { background:transparent; color:var(--text); border:1px solid var(--border); font-weight:500; }
    table { width:100%; border-collapse:collapse; font-size:0.88rem; }
    th, td { text-align:left; padding:0.5rem 0.55rem; border-bottom:1px solid var(--border); vertical-align:top; }
    th { color:var(--muted); font-weight:600; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.03em; }
    .mono { font-family:ui-monospace,Consolas,monospace; font-size:0.85rem; }
    .pill { display:inline-block; padding:0.12rem 0.45rem; border-radius:6px; background:#fff7ea; border:1px solid var(--border); font-size:0.8rem; }
    .vip { border-color:#b99138; color:#7a5500; }
    ul.lines { margin:0.2rem 0 0 1rem; padding:0; }
    .muted { color:var(--muted); font-size:0.85rem; margin-top:0.5rem; }
    .dt-footer {
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:1rem;
      padding:0.75rem 0.25rem 0.2rem;
      flex-wrap:wrap;
    }
    .dt-pagination {
      display:flex;
      align-items:center;
      gap:0.5rem;
      flex-wrap:wrap;
    }
    .dt-pagination a {
      text-decoration:none;
      padding:0.4rem 0.75rem;
      border:1px solid var(--border);
      border-radius:8px;
      color:var(--text);
      background:#fff7ea;
      font-size:0.85rem;
    }
    .dt-pagination a.active {
      border-color: var(--accent);
      color:#061018;
      background: var(--accent);
      font-weight:700;
    }
    .dt-pagination a.disabled {
      opacity: 0.45;
      pointer-events: none;
    }
    .btn-view {
      padding: 0.35rem 0.65rem;
      font-size: 0.85rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: #fffdfa;
      color: var(--accent);
      font-weight: 600;
      cursor: pointer;
    }
    .btn-view:hover { border-color: var(--accent); }
    .btn-void-toggle { border-color:#9a6b6b; color:#7a3e3e; background:#fff5f5; text-decoration:none; font-size:0.85rem; padding:0.45rem 0.75rem; border-radius:8px; display:inline-flex; align-items:center; }
    .btn-void-toggle.on { background:#5c2a2a; color:#fff; border-color:#5c2a2a; }
    .btn-void-record { border-color:#9a6b6b; color:#7a3e3e; background:#fff5f5; }
    .btn-void-record:hover { background:#5c2a2a; color:#fff; border-color:#5c2a2a; }
    .pill-voided { background:#f5e8e8; border-color:#c9a0a0; color:#6b3030; font-size:0.75rem; margin-left:0.35rem; }
    tr.row-voided { opacity:0.78; background:#faf8f6; }
    tr.row-damaged td { background:#fdeaea; }
    tr.row-damaged.row-voided td { background:#f8ecec; }
    .records-table td.td-claim-note {
      white-space: normal;
      max-width: 11rem;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .view-claim-box {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border);
    }
    .view-claim-box label {
      display: block;
      font-size: 0.78rem;
      color: var(--muted);
      margin: 0.5rem 0 0.2rem;
    }
    .view-claim-box select,
    .view-claim-box textarea {
      width: 100%;
      padding: 0.45rem 0.55rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      font-size: 0.9rem;
      font-family: inherit;
    }
    .view-claim-box textarea { min-height: 4.5rem; resize: vertical; }
    .btn-save-claim {
      margin-top: 0.75rem;
      background: var(--accent);
      color: #061018;
      border: none;
      font-weight: 600;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      cursor: pointer;
    }
    .btn-save-claim:disabled { opacity: 0.55; cursor: not-allowed; }
    .claim-save-msg { font-size: 0.85rem; margin-top: 0.5rem; }
    .claim-save-msg.ok { color: #166534; }
    .claim-save-msg.err { color: #991b1b; }
    .records-table-wrap {
      width: 100%;
      max-width: 100%;
      overflow-x: auto;
      overflow-y: visible;
      -webkit-overflow-scrolling: touch;
    }
    .records-table {
      width: 100%;
      min-width: 1020px;
      border-collapse: collapse;
    }
    .records-table th,
    .records-table td { white-space: nowrap; }
    .records-table td.td-lines,
    .records-table th.th-lines { white-space: normal; min-width: 10rem; max-width: 14rem; }
    .records-table td.td-tracking,
    .records-table td.td-dates { white-space: normal; }
    .records-table td.td-tracking { min-width: 9rem; }
    .records-table td.td-dates { min-width: 7.5rem; }
    .view-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(75,58,29,0.35);
      z-index: 120;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 1rem;
      overflow-y: auto;
      animation: fadeInView .18s ease both;
    }
    .view-backdrop.hidden { display: none; }
    .view-modal {
      width: min(520px, 100%);
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.1rem 1.2rem 1.25rem;
      margin: 3vh 0;
      box-shadow: 0 18px 40px rgba(101,76,36,0.18);
      animation: popInView .22s ease both;
    }
    .view-modal h2 { margin: 0 0 0.75rem; font-size: 1.05rem; }
    .view-modal dl { margin: 0; }
    .view-modal dt { color: var(--muted); font-size: 0.78rem; margin-top: 0.55rem; }
    .view-modal dd { margin: 0.15rem 0 0; }
    .view-modal img.doc-preview {
      max-width: 100%;
      max-height: 320px;
      border-radius: 10px;
      border: 1px solid var(--border);
      margin-top: 0.5rem;
      object-fit: contain;
      background: #fff9ef;
    }
    .doc-gallery { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; }
    .doc-gallery-item { flex: 1 1 220px; max-width: 100%; border: 1px solid var(--border); border-radius: 10px; padding: 0.5rem; background: #fff9ef; }
    .view-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
    .view-actions a, .view-actions button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.45rem 0.85rem;
      border-radius: 8px;
      font-size: 0.9rem;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid var(--border);
      background: #fffdfa;
      color: var(--text);
      font-weight: 500;
    }
    .view-actions button.hidden { display: none; }
    .view-actions .btn-void-record { margin-right: auto; }
    .view-actions a.download-link {
      background: var(--accent);
      border-color: var(--accent);
      color: #fffdf6;
      font-weight: 600;
    }
    @keyframes fadeInView { from { opacity: 0; } to { opacity: 1; } }
    @keyframes popInView { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .void-confirm-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(75, 58, 29, 0.45);
      z-index: 140;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .void-confirm-backdrop.hidden { display: none; }
    .void-confirm-modal {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.2rem 1.35rem;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 16px 48px rgba(101, 76, 36, 0.22);
    }
    .void-confirm-modal h3 { margin: 0 0 0.5rem; font-size: 1.05rem; color: #6b3030; }
    .void-confirm-modal .void-confirm-summary { font-size: 0.9rem; line-height: 1.5; margin: 0 0 1rem; color: var(--text); }
    .void-confirm-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: flex-end; }
    .void-confirm-actions .btn-danger-confirm {
      background: #5c2a2a;
      border: 1px solid #5c2a2a;
      color: #fff;
      font-weight: 600;
      padding: 0.45rem 0.85rem;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.9rem;
    }
    .void-confirm-actions .btn-cancel-confirm {
      background: #fffdfa;
      border: 1px solid var(--border);
      color: var(--text);
      padding: 0.45rem 0.85rem;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.9rem;
    }
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
      main { padding-top: 80px; padding-left: 0.75rem; padding-right: 0.75rem; }
      .filters { grid-template-columns: 1fr 1fr; }
      .filters .full { grid-column: 1 / -1; }
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
      <?php rts_admin_nav_links('records', '', $deliveryNavBadge); ?>
    </nav>
  </div>
  <aside class="sidebar">
    <div>
      <img src="assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:42px;width:auto;max-width:100%;border-radius:8px;margin-bottom:0.5rem;" onerror="this.style.display='none'">
      <h1>RTS system</h1>
      <p style="margin:0.25rem 0 0;font-size:0.78rem;color:var(--muted);">Returns tracking &amp; reporting</p>
    </div>
    <nav class="nav-links">
      <?php rts_admin_nav_links('records', '', $deliveryNavBadge); ?>
    </nav>
  </aside>
  <main>
    <div class="card">
      <form method="get" action="records.php" class="filters">
        <div class="full">
          <label for="q">Tracking contains</label>
          <input type="text" id="q" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Partial tracking…">
        </div>
        <div>
          <label for="shop">Shop</label>
          <select id="shop" name="shop">
            <option value="">All</option>
            <option value="shopee" <?= $shop === 'shopee' ? 'selected' : '' ?>>Shopee</option>
            <option value="lazada" <?= $shop === 'lazada' ? 'selected' : '' ?>>Lazada</option>
            <option value="tiktok" <?= $shop === 'tiktok' ? 'selected' : '' ?>>TikTok</option>
            <option value="vip" <?= $shop === 'vip' ? 'selected' : '' ?>>VIP only</option>
          </select>
        </div>
        <div>
          <label for="courier">Courier</label>
          <select id="courier" name="courier">
            <option value="">All</option>
            <option value="spx" <?= $courier === 'spx' ? 'selected' : '' ?>>SPX</option>
            <option value="jnt" <?= $courier === 'jnt' ? 'selected' : '' ?>>J&amp;T</option>
            <option value="flash" <?= $courier === 'flash' ? 'selected' : '' ?>>Flash</option>
            <option value="unknown" <?= $courier === 'unknown' ? 'selected' : '' ?>>Unknown</option>
          </select>
        </div>
        <div>
          <label for="damage">Damage</label>
          <select id="damage" name="damage">
            <option value="">All records</option>
            <option value="all_damaged" <?= $damage === 'all_damaged' ? 'selected' : '' ?>>All damaged</option>
            <option value="not_damaged" <?= $damage === 'not_damaged' ? 'selected' : '' ?>>Not damaged</option>
            <option value="leak" <?= $damage === 'leak' ? 'selected' : '' ?>>Leak</option>
            <option value="item_replaced" <?= $damage === 'item_replaced' ? 'selected' : '' ?>>Item replaced</option>
            <option value="packaging_replaced" <?= $damage === 'packaging_replaced' ? 'selected' : '' ?>>Packaging replaced</option>
          </select>
        </div>
        <div>
          <label for="vip_only">VIP shortcut</label>
          <select id="vip_only" name="vip_only">
            <option value="">Off</option>
            <option value="1" <?= $vipOnly ? 'selected' : '' ?>>VIP orders only</option>
          </select>
        </div>
        <div>
          <label for="date_from">Opened from</label>
          <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
          <label for="date_to">Opened to</label>
          <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
          <label for="del_from">Delivered from</label>
          <input type="date" id="del_from" name="del_from" value="<?= htmlspecialchars($delFrom, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
          <label for="del_to">Delivered to</label>
          <input type="date" id="del_to" name="del_to" value="<?= htmlspecialchars($delTo, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php if ($voidOnly): ?>
        <input type="hidden" name="void_only" value="1">
        <?php endif; ?>
        <input type="hidden" name="page" value="1">
        <div class="full" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.25rem;align-items:center;">
          <button type="submit" class="btn">Apply filters</button>
          <a class="btn btn-secondary" href="records.php" style="text-decoration:none;line-height:1.2;">Clear</a>
          <span style="flex:1;min-width:0.5rem;"></span>
          <span style="font-size:0.78rem;color:var(--muted);">Voided</span>
          <?php if ($voidOnly): ?>
          <a class="btn-void-toggle on" href="<?= htmlspecialchars(records_url(['void_only' => '']), ENT_QUOTES, 'UTF-8') ?>">Voided only (on)</a>
          <?php else: ?>
          <a class="btn-void-toggle" href="<?= htmlspecialchars(records_url(['void_only' => '1']), ENT_QUOTES, 'UTF-8') ?>">Voided only</a>
          <?php endif; ?>
        </div>
      </form>
      <p class="muted" style="margin-top:0.5rem;">Damaged parcels appear first (red tint), then by open date. Voided entries sink to the bottom. Claim status is edited in <strong>View</strong> only.</p>
      <p class="muted">Showing <?= (int) $start ?> to <?= (int) $end ?> of <?= (int) $totalCount ?> entries</p>
    </div>

    <div class="card records-table-wrap">
      <table class="records-table">
        <thead>
          <tr>
            <th>Tracking</th>
            <th>Shop</th>
            <th>Courier</th>
            <th>Dates</th>
            <th>Damage</th>
            <th>Claim status</th>
            <th class="th-claim-note">Claim note</th>
            <th class="th-lines">Lines</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($cases === []): ?>
          <tr><td colspan="9">No records match.</td></tr>
          <?php else: ?>
          <?php foreach ($cases as $c): ?>
          <?php
              $cid = (int) $c['id'];
              $lines = $linesByCase[$cid] ?? [];
              $shopHtml = rts_shop_label((string) $c['shop']);
              if ($c['shop'] === 'vip' && !empty($c['vip_subsource'])) {
                  $shopHtml .= ' — ' . ($c['vip_subsource'] === 'website' ? 'Website' : 'Facebook');
              }
              $linesPlain = [];
              foreach ($lines as $L) {
                  $linesPlain[] = html_entity_decode((string) rts_line_summary($L), ENT_QUOTES | ENT_HTML5, 'UTF-8');
              }
              $docAttachments = rts_attachments_decode(
                  isset($c['documentation_attachments']) ? (string) $c['documentation_attachments'] : null,
                  isset($c['documentation_photo']) ? (string) $c['documentation_photo'] : null
              );
              $dmgTag = isset($c['damaged_tag']) && (string) $c['damaged_tag'] !== ''
                  ? (string) $c['damaged_tag']
                  : 'no';
              $isDamaged = !empty($c['damaged_reason']);
              $claimStatus = isset($c['claim_status']) && (string) $c['claim_status'] !== ''
                  ? (string) $c['claim_status']
                  : 'for_submission';
              if (!rts_claim_status_valid($claimStatus)) {
                  $claimStatus = 'for_submission';
              }
              $claimNote = isset($c['claim_status_note']) ? trim((string) $c['claim_status_note']) : '';
              $voided = !empty($c['voided_at']);
              $viewData = [
                  'caseId' => $cid,
                  'voided' => $voided,
                  'voidedAt' => $voided ? (string) $c['voided_at'] : '',
                  'tracking' => (string) $c['tracking_number'],
                  'shop' => $shopHtml,
                  'courierFinal' => rts_courier_label((string) $c['courier_final']),
                  'courierDetected' => rts_courier_label((string) $c['courier_detected']),
                  'dateDelivered' => $c['date_delivered'] ? (string) $c['date_delivered'] : '',
                  'dateOpened' => (string) $c['date_opened'],
                  'createdAt' => (string) $c['created_at'],
                  'damagedTag' => $dmgTag,
                  'isDamaged' => $isDamaged,
                  'claimStatus' => $claimStatus,
                  'claimStatusNote' => $claimNote,
                  'documentationAttachments' => $docAttachments,
                  'lines' => $linesPlain,
              ];
              $viewJson = htmlspecialchars(
                  json_encode($viewData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                  ENT_QUOTES,
                  'UTF-8'
              );
              $rowClasses = [];
              if ($voided) {
                  $rowClasses[] = 'row-voided';
              }
              if ($isDamaged) {
                  $rowClasses[] = 'row-damaged';
              }
              $rowClass = implode(' ', $rowClasses);
              $claimNoteShort = $claimNote;
              if (strlen($claimNoteShort) > 80) {
                  $claimNoteShort = substr($claimNoteShort, 0, 77) . '…';
              }
              ?>
          <tr class="<?= htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8') ?>"
              data-voided="<?= $voided ? '1' : '0' ?>"
              data-case-id="<?= $cid ?>"
              data-claim-status="<?= htmlspecialchars($claimStatus, ENT_QUOTES, 'UTF-8') ?>">
            <td class="mono td-tracking">
              <?= htmlspecialchars((string) $c['tracking_number'], ENT_QUOTES, 'UTF-8') ?>
              <?php if ($voided): ?>
              <span class="pill pill-voided">Voided</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="pill <?= $c['shop'] === 'vip' ? 'vip' : '' ?>"><?= htmlspecialchars($shopHtml, ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td><?= htmlspecialchars(rts_courier_label((string) $c['courier_final']), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="td-dates">
              <small style="color:var(--muted);">Delivered</small>
              <?= $c['date_delivered'] ? htmlspecialchars((string) $c['date_delivered'], ENT_QUOTES, 'UTF-8') : '—' ?>
              <br><small style="color:var(--muted);">Opened</small>
              <?= htmlspecialchars((string) $c['date_opened'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td style="font-size:0.85rem;">
              <?= htmlspecialchars($dmgTag, ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="td-claim-status"><?= htmlspecialchars(rts_claim_status_label($claimStatus), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="td-claim-note" title="<?= htmlspecialchars($claimNote, ENT_QUOTES, 'UTF-8') ?>">
              <?= $claimNote !== '' ? htmlspecialchars($claimNoteShort, ENT_QUOTES, 'UTF-8') : '—' ?>
            </td>
            <td class="td-lines">
              <?php if ($lines === []): ?>
              <em style="color:var(--muted);">No lines</em>
              <?php else: ?>
              <ul class="lines">
                <?php foreach ($lines as $L): ?>
                <li><?= rts_line_summary($L) ?></li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </td>
            <td class="td-actions">
              <button type="button" class="btn-view view-open" data-case="<?= $viewJson ?>">View</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="dt-footer">
        <div class="muted">
          Page <?= (int) $page ?> of <?= (int) $totalPages ?>
        </div>
        <div class="dt-pagination">
          <?php if ($page <= 1): ?>
            <a class="disabled">Previous</a>
          <?php else: ?>
            <a href="<?= htmlspecialchars(records_page_url((int) $page - 1), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
          <?php endif; ?>

          <?php if ($page >= $totalPages): ?>
            <a class="disabled">Next</a>
          <?php else: ?>
            <a href="<?= htmlspecialchars(records_page_url((int) $page + 1), ENT_QUOTES, 'UTF-8') ?>">Next</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
  <div id="viewBackdrop" class="view-backdrop hidden" aria-hidden="true">
    <div class="view-modal" role="dialog" aria-modal="true" aria-labelledby="viewDialogTitle">
      <h2 id="viewDialogTitle">Record details</h2>
      <dl id="viewFields"></dl>
      <dl id="viewDocSection" style="margin-top:0.5rem;"></dl>
      <div class="view-claim-box" id="viewClaimBox">
        <h3 style="margin:0 0 0.35rem;font-size:0.95rem;">Claim status</h3>
        <p style="margin:0 0 0.5rem;font-size:0.82rem;color:var(--muted);">Update status and note here only.</p>
        <label for="viewClaimStatus">Status</label>
        <select id="viewClaimStatus">
          <?php foreach (rts_claim_status_options() as $opt): ?>
          <option value="<?= htmlspecialchars($opt['value'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($opt['label'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <label for="viewClaimNote">Note</label>
        <textarea id="viewClaimNote" maxlength="512" placeholder="Optional note for this claim…"></textarea>
        <button type="button" class="btn-save-claim" id="viewSaveClaim">Save claim status</button>
        <p class="claim-save-msg hidden" id="viewClaimMsg" role="status"></p>
      </div>
      <div class="view-actions">
        <button type="button" class="btn-void-record hidden" id="viewVoid">Void record</button>
        <button type="button" id="viewClose">Close</button>
      </div>
    </div>
  </div>
  <div id="voidConfirmBackdrop" class="void-confirm-backdrop hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="voidConfirmTitle">
    <div class="void-confirm-modal">
      <h3 id="voidConfirmTitle">Void this RTS record?</h3>
      <p id="voidConfirmSummary" class="void-confirm-summary"></p>
      <div class="void-confirm-actions">
        <button type="button" class="btn-cancel-confirm" id="voidConfirmCancel">Cancel</button>
        <button type="button" class="btn-danger-confirm" id="voidConfirmYes">Yes, void record</button>
      </div>
    </div>
  </div>
  <script>
  var RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;
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
      a.addEventListener('click', function () {
        setOpen(false);
      });
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
      if (e.key !== 'Escape') return;
      var vcb = document.getElementById('voidConfirmBackdrop');
      if (vcb && !vcb.classList.contains('hidden')) {
        vcb.classList.add('hidden');
        vcb.setAttribute('aria-hidden', 'true');
        return;
      }
      var vb = document.getElementById('viewBackdrop');
      if (vb && !vb.classList.contains('hidden')) {
        vb.classList.add('hidden');
        vb.setAttribute('aria-hidden', 'true');
        return;
      }
      setOpen(false);
    });
  })();

  (function () {
    var backdrop = document.getElementById('viewBackdrop');
    var fields = document.getElementById('viewFields');
    var docSection = document.getElementById('viewDocSection');
    var closeBtn = document.getElementById('viewClose');
    var voidBtn = document.getElementById('viewVoid');
    var voidConfirmBackdrop = document.getElementById('voidConfirmBackdrop');
    var voidConfirmSummary = document.getElementById('voidConfirmSummary');
    var voidConfirmCancel = document.getElementById('voidConfirmCancel');
    var voidConfirmYes = document.getElementById('voidConfirmYes');
    var claimStatusEl = document.getElementById('viewClaimStatus');
    var claimNoteEl = document.getElementById('viewClaimNote');
    var saveClaimBtn = document.getElementById('viewSaveClaim');
    var claimMsgEl = document.getElementById('viewClaimMsg');
    var currentCaseId = 0;
    var currentVoidTracking = '';
    var claimLabels = {
      for_submission: 'For submission',
      denied_claims: 'Denied claims',
      approved_claims: 'Approved claims',
      submitted_claims: 'Submitted claims',
      on_process: 'On process'
    };
    if (!backdrop || !fields || !docSection || !closeBtn) return;

    function esc(s) {
      if (s === null || s === undefined) return '';
      return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function closeView() {
      backdrop.classList.add('hidden');
      backdrop.setAttribute('aria-hidden', 'true');
      currentCaseId = 0;
      if (voidBtn) voidBtn.classList.add('hidden');
      if (claimMsgEl) {
        claimMsgEl.classList.add('hidden');
        claimMsgEl.textContent = '';
        claimMsgEl.classList.remove('ok', 'err');
      }
    }

    function truncateNote(s, max) {
      if (!s) return '—';
      if (s.length <= max) return s;
      return s.slice(0, max - 1) + '…';
    }

    function updateRowClaim(caseId, statusCode, note, statusLabel) {
      var row = document.querySelector('tr[data-case-id="' + caseId + '"]');
      if (!row) return;
      var label = statusLabel || claimLabels[statusCode] || statusCode;
      var statusTd = row.querySelector('.td-claim-status');
      var noteTd = row.querySelector('.td-claim-note');
      if (statusTd) statusTd.textContent = label;
      if (noteTd) {
        noteTd.textContent = note ? truncateNote(note, 80) : '—';
        noteTd.setAttribute('title', note || '');
      }
      row.setAttribute('data-claim-status', statusCode);
    }

    function openView(jsonStr) {
      var d;
      try {
        d = JSON.parse(jsonStr);
      } catch (err) {
        return;
      }
      var html = '';
      html += '<dt>Tracking</dt><dd class="mono">' + esc(d.tracking) + '</dd>';
      html += '<dt>Shop</dt><dd>' + esc(d.shop) + '</dd>';
      html += '<dt>Courier (final)</dt><dd>' + esc(d.courierFinal) + '</dd>';
      html += '<dt>Courier (from tracking)</dt><dd>' + esc(d.courierDetected) + '</dd>';
      html += '<dt>Date delivered</dt><dd>' + esc(d.dateDelivered || '—') + '</dd>';
      html += '<dt>Date opened</dt><dd>' + esc(d.dateOpened) + '</dd>';
      html += '<dt>Recorded</dt><dd>' + esc(d.createdAt) + '</dd>';
      html += '<dt>Damaged</dt><dd>' + esc(d.damagedTag || 'no') + '</dd>';
      if (d.voided) {
        html += '<dt>Status</dt><dd><span class="pill pill-voided">Voided</span>';
        if (d.voidedAt) html += ' <span style="font-size:0.85rem;color:var(--muted);">' + esc(d.voidedAt) + '</span>';
        html += '</dd>';
      }
      html += '<dt>Line items</dt><dd>';
      if (d.lines && d.lines.length) {
        html += '<ul class="lines" style="margin-left:1rem;">';
        for (var i = 0; i < d.lines.length; i++) {
          html += '<li>' + esc(d.lines[i]) + '</li>';
        }
        html += '</ul>';
      } else {
        html += '<em style="color:var(--muted);">No lines</em>';
      }
      html += '</dd>';
      fields.innerHTML = html;

      var att = d.documentationAttachments;
      if (!Array.isArray(att)) att = [];
      if (!att.length && d.documentationPhoto && /^[a-zA-Z0-9_.-]+$/.test(String(d.documentationPhoto))) {
        att = [{ kind: 'image', file: String(d.documentationPhoto) }];
      }
      var docHtml = '';
      docHtml += '<dt style="color:var(--muted);font-size:0.78rem;">RTS proof (photos / videos)</dt><dd>';
      if (!att.length) {
        docHtml += '<em style="color:var(--muted);">No documentation uploaded</em>';
      } else {
        docHtml += '<div class="doc-gallery">';
        for (var i = 0; i < att.length; i++) {
          var item = att[i];
          var fn = item && item.file ? String(item.file) : '';
          var k = item && item.kind ? String(item.kind) : 'image';
          if (!/^[a-zA-Z0-9_.-]+$/.test(fn)) continue;
          var url = RTS_DOC_URL_BASE + encodeURIComponent(fn);
          docHtml += '<div class="doc-gallery-item">';
          if (k === 'video') {
            docHtml += '<video controls playsinline preload="metadata" src="' + esc(url) + '" style="max-width:100%;max-height:240px;border-radius:8px;"></video>';
          } else {
            docHtml += '<img class="doc-preview" src="' + esc(url) + '" alt="Documentation" style="max-height:240px;">';
          }
          docHtml += '<div style="margin-top:0.35rem;"><a class="download-link" href="' + esc(url) + '" download="' + esc(fn) + '">Download</a></div>';
          docHtml += '</div>';
        }
        docHtml += '</div>';
      }
      docHtml += '</dd>';
      docSection.innerHTML = docHtml;

      currentCaseId = d.caseId ? parseInt(d.caseId, 10) : 0;
      currentVoidTracking = d.tracking ? String(d.tracking) : '';
      if (claimStatusEl) {
        claimStatusEl.value = d.claimStatus && claimLabels[d.claimStatus] ? d.claimStatus : 'for_submission';
      }
      if (claimNoteEl) {
        claimNoteEl.value = d.claimStatusNote ? String(d.claimStatusNote) : '';
      }
      if (claimMsgEl) {
        claimMsgEl.classList.add('hidden');
        claimMsgEl.textContent = '';
        claimMsgEl.classList.remove('ok', 'err');
      }
      if (voidBtn) {
        if (!d.voided && currentCaseId > 0) {
          voidBtn.classList.remove('hidden');
        } else {
          voidBtn.classList.add('hidden');
        }
      }

      backdrop.classList.remove('hidden');
      backdrop.setAttribute('aria-hidden', 'false');
    }

    function closeVoidConfirm() {
      if (!voidConfirmBackdrop) return;
      voidConfirmBackdrop.classList.add('hidden');
      voidConfirmBackdrop.setAttribute('aria-hidden', 'true');
      if (voidConfirmYes) {
        voidConfirmYes.disabled = false;
        voidConfirmYes.textContent = 'Yes, void record';
      }
    }

    function openVoidConfirm() {
      if (currentCaseId < 1 || !voidConfirmBackdrop) return;
      if (voidConfirmSummary) {
        var msg = 'Tracking: <strong class="mono">' + esc(currentVoidTracking || '—') + '</strong><br><br>';
        msg += 'The record will stay in the list for audit but be marked <strong>voided</strong>. Photos and videos are not deleted.';
        voidConfirmSummary.innerHTML = msg;
      }
      voidConfirmBackdrop.classList.remove('hidden');
      voidConfirmBackdrop.setAttribute('aria-hidden', 'false');
      if (voidConfirmYes) voidConfirmYes.focus();
    }

    function doVoidRecord() {
      if (currentCaseId < 1) return;
      if (voidConfirmYes) {
        voidConfirmYes.disabled = true;
        voidConfirmYes.textContent = 'Voiding…';
      }
      fetch('api/case.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'void', case_id: currentCaseId })
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (j.ok) {
          window.location.reload();
        } else {
          alert(j.error || j.message || 'Void failed');
          if (voidConfirmYes) {
            voidConfirmYes.disabled = false;
            voidConfirmYes.textContent = 'Yes, void record';
          }
        }
      }).catch(function () {
        alert('Network error');
        if (voidConfirmYes) {
          voidConfirmYes.disabled = false;
          voidConfirmYes.textContent = 'Yes, void record';
        }
      });
    }

    function saveClaimStatus() {
      if (currentCaseId < 1 || !claimStatusEl) return;
      var status = claimStatusEl.value;
      var note = claimNoteEl ? String(claimNoteEl.value || '').trim() : '';
      if (saveClaimBtn) saveClaimBtn.disabled = true;
      if (claimMsgEl) {
        claimMsgEl.classList.add('hidden');
        claimMsgEl.classList.remove('ok', 'err');
      }
      fetch('api/case.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          action: 'update_claim',
          case_id: currentCaseId,
          claim_status: status,
          claim_status_note: note
        })
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (saveClaimBtn) saveClaimBtn.disabled = false;
        if (!j.ok) {
          if (claimMsgEl) {
            claimMsgEl.textContent = j.error || j.message || 'Save failed';
            claimMsgEl.classList.remove('hidden');
            claimMsgEl.classList.add('err');
          }
          return;
        }
        updateRowClaim(currentCaseId, j.claim_status, j.claim_status_note, j.claim_status_label);
        if (claimMsgEl) {
          claimMsgEl.textContent = 'Claim status saved.';
          claimMsgEl.classList.remove('hidden', 'err');
          claimMsgEl.classList.add('ok');
        }
      }).catch(function () {
        if (saveClaimBtn) saveClaimBtn.disabled = false;
        if (claimMsgEl) {
          claimMsgEl.textContent = 'Network error';
          claimMsgEl.classList.remove('hidden');
          claimMsgEl.classList.add('err');
        }
      });
    }

    if (saveClaimBtn) {
      saveClaimBtn.addEventListener('click', saveClaimStatus);
    }
    if (voidBtn) {
      voidBtn.addEventListener('click', openVoidConfirm);
    }
    if (voidConfirmCancel) voidConfirmCancel.addEventListener('click', closeVoidConfirm);
    if (voidConfirmYes) voidConfirmYes.addEventListener('click', doVoidRecord);
    if (voidConfirmBackdrop) {
      voidConfirmBackdrop.addEventListener('click', function (e) {
        if (e.target === voidConfirmBackdrop) closeVoidConfirm();
      });
    }

    document.querySelectorAll('.view-open').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var raw = btn.getAttribute('data-case');
        if (raw) openView(raw);
      });
    });

    closeBtn.addEventListener('click', closeView);
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) closeView();
    });
    backdrop.querySelector('.view-modal').addEventListener('click', function (e) {
      e.stopPropagation();
    });
  })();
  </script>
</body>
</html>
