<?php
/**
 * Honey Order Editor - Web System
 * Parses remarks from JNT export and allows editing → clean remarks
 */

require_once __DIR__ . '/auth_gate.php';

$UPLOAD_DIR = __DIR__ . '/uploads/';
$OUTPUT_DIR = __DIR__ . '/output/';

if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);
if (!is_dir($OUTPUT_DIR)) mkdir($OUTPUT_DIR, 0755, true);

// Handle file upload
$uploadMessage = '';
$uploadedFile = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['orders_file'])) {
    $file = $_FILES['orders_file'];
    if ($file['error'] === 0 && pathinfo($file['name'], PATHINFO_EXTENSION) === 'xlsx') {
        $dest = $UPLOAD_DIR . 'orders_latest.xlsx';
        move_uploaded_file($file['tmp_name'], $dest);
        $uploadMessage = 'success';
        $uploadedFile = $dest;
    } else {
        $uploadMessage = 'error';
    }
}

// Use default file if exists
$ordersFile = $UPLOAD_DIR . 'orders_latest.xlsx';
if (!file_exists($ordersFile)) {
    // Try to copy bundled sample
    if (file_exists(__DIR__ . '/orders.xlsx')) {
        copy(__DIR__ . '/orders.xlsx', $ordersFile);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🍯 Honey Order Editor</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap');

  :root {
    --honey:     #E8A020;
    --honey-deep:#B87010;
    --amber:     #C67C1A;
    --light-h:   #FFF0CC;
    --dark-h:    #3D2000;
    --bg:        #FFF8EE;
    --surface:   #FFFDF8;
    --border:    #F0D080;
    --text:      #2A1800;
    --muted:     #8B6840;
    --light-tag: #FFF3C0;
    --amber-tag: #FFD980;
    --dark-tag:  #A0622A;
    --green:     #2D7D46;
    --red:       #C0392B;
    --shadow:    0 2px 12px rgba(180,100,0,0.12);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Space Mono', monospace;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* ── HEADER ── */
  header {
    background: linear-gradient(135deg, var(--dark-h) 0%, #5A3200 100%);
    padding: 20px 32px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  }
  header .logo { font-size: 2rem; }
  header h1 {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.5rem;
    color: var(--honey);
    letter-spacing: -0.5px;
  }
  header p { color: #C09050; font-size: 0.75rem; margin-top: 2px; }
  header .spacer { flex: 1; }
  .header-stats {
    display: flex;
    gap: 16px;
  }
  .stat-pill {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(232,160,32,0.3);
    border-radius: 20px;
    padding: 6px 14px;
    text-align: center;
  }
  .stat-pill .num { color: var(--honey); font-size: 1.1rem; font-weight: 700; display: block; }
  .stat-pill .lbl { color: #C09050; font-size: 0.65rem; }

  /* ── MAIN LAYOUT ── */
  .main-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 24px;
  }

  /* ── UPLOAD BAR ── */
  .upload-bar {
    background: var(--surface);
    border: 2px dashed var(--border);
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .upload-bar label {
    font-family: 'Syne', sans-serif;
    font-weight: 600;
    color: var(--muted);
    font-size: 0.85rem;
    white-space: nowrap;
  }
  .upload-bar input[type=file] {
    flex: 1;
    font-family: inherit;
    font-size: 0.8rem;
    color: var(--text);
  }
  .btn-upload {
    background: var(--honey);
    color: var(--dark-h);
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.2s;
  }
  .btn-upload:hover { background: var(--honey-deep); color: #fff; }

  .msg-success { color: var(--green); font-size: 0.8rem; }
  .msg-error   { color: var(--red);   font-size: 0.8rem; }

  /* ── FILTER BAR ── */
  .filter-bar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
  }
  .filter-bar label {
    font-family: 'Syne', sans-serif;
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--muted);
    white-space: nowrap;
  }
  .filter-btn {
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 5px 14px;
    font-family: 'Space Mono', monospace;
    font-size: 0.75rem;
    cursor: pointer;
    background: white;
    color: var(--text);
    transition: all 0.15s;
  }
  .filter-btn:hover, .filter-btn.active { background: var(--honey); border-color: var(--honey); color: var(--dark-h); font-weight: 700; }
  #search-input {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 6px 12px;
    font-family: 'Space Mono', monospace;
    font-size: 0.78rem;
    background: white;
    color: var(--text);
    outline: none;
    width: 220px;
  }
  #search-input:focus { border-color: var(--honey); }
  .filter-spacer { flex: 1; }
  .counter-label { font-size: 0.75rem; color: var(--muted); }

  /* ── TABLE ── */
  .table-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
  }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: var(--dark-h); }
  thead th {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.72rem;
    color: var(--honey);
    padding: 12px 14px;
    text-align: left;
    letter-spacing: 0.5px;
    white-space: nowrap;
  }
  tbody tr {
    border-bottom: 1px solid #F5E8CC;
    transition: background 0.1s;
  }
  tbody tr:hover { background: #FFFBF0; }
  tbody tr.row-dirty { background: #FFF8E0; }
  tbody tr.row-saved { background: #F0FFF4; }
  tbody td { padding: 10px 14px; font-size: 0.78rem; vertical-align: top; }

  .col-num  { width: 44px; color: var(--muted); text-align: center; }
  .col-name { min-width: 140px; max-width: 200px; }
  .col-type { width: 110px; }
  .col-raw  { min-width: 200px; max-width: 280px; }
  .col-edit { min-width: 280px; }
  .col-act  { width: 100px; text-align: center; }

  .receiver-name { font-weight: 700; color: var(--dark-h); }
  .receiver-phone { color: var(--muted); font-size: 0.7rem; margin-top: 2px; }

  /* ── ORDER TYPE BADGE ── */
  .type-badge {
    display: inline-block;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: 0.7rem;
    font-weight: 700;
    font-family: 'Syne', sans-serif;
    white-space: nowrap;
  }
  .type-3l  { background: #3D2000; color: #FFD080; }
  .type-2l  { background: #5A3800; color: #FFCD70; }
  .type-1l  { background: #7A5000; color: #FFD090; }
  .type-350 { background: #C08020; color: white; }
  .type-other { background: #888; color: white; }

  /* ── VARIANTS PARSED ── */
  .variants-parsed {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 6px;
  }
  .var-chip {
    border-radius: 4px;
    padding: 2px 7px;
    font-size: 0.7rem;
    font-weight: 700;
    font-family: 'Syne', sans-serif;
  }
  .var-light  { background: var(--light-tag); color: #6B4C00; border: 1px solid #FFE080; }
  .var-amber  { background: var(--amber-tag); color: #5A3000; border: 1px solid #FFC040; }
  .var-dark   { background: var(--dark-tag);  color: white;   border: 1px solid #8B4A1A; }
  .var-lad    { background: linear-gradient(90deg,#FFF3C0,#FFD980,#A0622A); color: #3D2000; border: 1px solid #C07820; }
  .var-ad     { background: linear-gradient(90deg,#FFD980,#A0622A); color: white; border: 1px solid #8B4A1A; }
  .var-la     { background: linear-gradient(90deg,#FFF3C0,#FFD980); color: #5A3000; border: 1px solid #FFC040; }
  .var-ld     { background: linear-gradient(90deg,#FFF3C0,#A0622A); color: #3D2000; border: 1px solid #C07820; }

  /* ── CLEAN REMARKS EDITOR ── */
  .clean-remarks-wrap { position: relative; }
  .clean-remarks {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 8px 10px;
    font-family: 'Space Mono', monospace;
    font-size: 0.75rem;
    color: var(--dark-h);
    background: white;
    resize: vertical;
    min-height: 60px;
    transition: border-color 0.2s;
    outline: none;
    line-height: 1.5;
  }
  .clean-remarks:focus { border-color: var(--honey); box-shadow: 0 0 0 3px rgba(232,160,32,0.15); }
  .clean-remarks.modified { border-color: #E8A020; background: #FFFDF0; }
  .clean-remarks.saved { border-color: var(--green); background: #F8FFF8; }

  .quick-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 5px;
  }
  .qb {
    border: 1px solid var(--border);
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 0.65rem;
    font-family: 'Syne', sans-serif;
    font-weight: 600;
    cursor: pointer;
    background: white;
    color: var(--text);
    transition: all 0.15s;
    white-space: nowrap;
  }
  .qb:hover { background: var(--honey); border-color: var(--honey); color: var(--dark-h); }

  /* ── ACTION BUTTONS ── */
  .btn-save {
    background: var(--honey);
    color: var(--dark-h);
    border: none;
    border-radius: 7px;
    padding: 6px 14px;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
  }
  .btn-save:hover { background: var(--honey-deep); color: white; }
  .btn-save:disabled { background: #D0C0A0; color: #8B7050; cursor: not-allowed; }
  .saved-indicator {
    color: var(--green);
    font-size: 0.7rem;
    display: none;
    margin-top: 4px;
  }

  /* ── EXPORT BAR ── */
  .export-bar {
    margin-top: 24px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
  }
  .export-bar p { font-size: 0.82rem; color: var(--muted); flex: 1; }
  .btn-export {
    background: var(--dark-h);
    color: var(--honey);
    border: none;
    border-radius: 9px;
    padding: 10px 24px;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .btn-export:hover { background: #5A3200; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

  /* ── RAW TEXT COLLAPSE ── */
  .raw-text {
    color: var(--muted);
    font-size: 0.68rem;
    line-height: 1.4;
    max-height: 60px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
  }
  .raw-text.expanded { max-height: none; }
  .raw-text::after {
    content: '▾ show more';
    display: block;
    color: var(--honey-deep);
    font-size: 0.65rem;
    margin-top: 2px;
  }
  .raw-text.expanded::after { content: '▴ collapse'; }

  /* ── LOADING / EMPTY ── */
  .loading { text-align: center; padding: 60px 20px; color: var(--muted); font-size: 0.9rem; }
  .no-file-notice {
    background: #FFF8E0;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 20px;
    font-size: 0.82rem;
    color: #8B5A00;
  }

  /* ── ADDRESS HINT ── */
  .address-hint { color: var(--muted); font-size: 0.68rem; margin-top: 3px; font-style: italic; }

  /* responsive */
  @media (max-width: 900px) {
    .col-raw { display: none; }
    .header-stats { display: none; }
  }
</style>
</head>
<body>

<header>
  <div class="logo">🍯</div>
  <div>
    <h1>Honey Order Editor</h1>
    <p>JNT Export — Remarks Cleaner</p>
  </div>
  <div class="spacer"></div>
  <div class="header-stats" id="headerStats">
    <div class="stat-pill"><span class="num" id="statTotal">—</span><span class="lbl">Total</span></div>
    <div class="stat-pill"><span class="num" id="statEdited">0</span><span class="lbl">Edited</span></div>
    <div class="stat-pill"><span class="num" id="statPending">—</span><span class="lbl">Pending</span></div>
  </div>
</header>

<div class="main-wrap">

  <!-- Upload -->
  <form method="POST" enctype="multipart/form-data" class="upload-bar">
    <label>📂 Upload new orders .xlsx:</label>
    <input type="file" name="orders_file" accept=".xlsx">
    <button type="submit" class="btn-upload">Upload</button>
    <?php if ($uploadMessage === 'success'): ?>
      <span class="msg-success">✓ Uploaded successfully</span>
    <?php elseif ($uploadMessage === 'error'): ?>
      <span class="msg-error">✗ Upload failed — must be .xlsx</span>
    <?php endif; ?>
  </form>

  <?php if (!file_exists($ordersFile)): ?>
  <div class="no-file-notice">⚠ No orders file found. Upload your JNT export .xlsx above to get started.</div>
  <?php endif; ?>

  <!-- Filter bar -->
  <div class="filter-bar">
    <label>Filter:</label>
    <button class="filter-btn active" data-filter="all" onclick="setFilter('all',this)">All</button>
    <button class="filter-btn" data-filter="3L" onclick="setFilter('3L',this)">3L Honey</button>
    <button class="filter-btn" data-filter="2L" onclick="setFilter('2L',this)">2L Honey</button>
    <button class="filter-btn" data-filter="1L" onclick="setFilter('1L',this)">1L Honey</button>
    <button class="filter-btn" data-filter="350ml" onclick="setFilter('350ml',this)">350mL</button>
    <button class="filter-btn" data-filter="pending" onclick="setFilter('pending',this)">Needs Edit</button>
    <button class="filter-btn" data-filter="edited" onclick="setFilter('edited',this)">Edited</button>
    <div class="filter-spacer"></div>
    <input type="text" id="search-input" placeholder="🔍 Search by name..." oninput="doSearch(this.value)">
    <span class="counter-label"><span id="visibleCount">—</span> shown</span>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table id="ordersTable">
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th class="col-name">Receiver</th>
          <th class="col-type">Product</th>
          <th class="col-raw">Raw Remarks</th>
          <th class="col-edit">Clean Remarks <span style="font-weight:400;opacity:.6">(edit below)</span></th>
          <th class="col-act">Save</th>
        </tr>
      </thead>
      <tbody id="ordersBody">
        <tr><td colspan="6" class="loading">Loading orders…</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Export -->
  <div class="export-bar">
    <p>When done editing, export the updated Excel file with all clean remarks written into column M.</p>
    <button class="btn-export" onclick="exportExcel()">
      ⬇ Export Updated Excel
    </button>
  </div>

</div>

<script>
// ──────────────────────────────────────────────
//  STATE
// ──────────────────────────────────────────────
let allOrders = [];
let editedMap = {}; // rowIndex → clean remarks string
let currentFilter = 'all';
let currentSearch = '';

// ──────────────────────────────────────────────
//  LOAD ORDERS
// ──────────────────────────────────────────────
async function loadOrders() {
  try {
    const res = await fetch('api.php?action=get_orders');
    const data = await res.json();
    if (data.error) { showError(data.error); return; }
    allOrders = data.orders;
    renderTable();
    updateStats();
  } catch(e) {
    showError('Failed to load orders: ' + e.message);
  }
}

function showError(msg) {
  document.getElementById('ordersBody').innerHTML =
    `<tr><td colspan="6" class="loading" style="color:#C0392B">⚠ ${msg}</td></tr>`;
}

// ──────────────────────────────────────────────
//  PARSE REMARKS
// ──────────────────────────────────────────────
function parseRemarks(raw) {
  if (!raw) return { product: 'other', variants: [], address: '', clean: '', needsEdit: false };

  const r = raw.trim();

  // Detect product type
  let product = 'other';
  if (/3L-HONEY|3 L Honey|3L HONEY/i.test(r)) product = '3L';
  else if (/2L-HONEY|2 L.Honey|2L HONEY/i.test(r)) product = '2L';
  else if (/1L HONEY|1 L HONEY/i.test(r)) product = '1L';
  else if (/350ml|350ML|B2T1/i.test(r)) product = '350ml';

  // Already clean (no raw form inputs)
  if (!/textarea_input|radio_input/i.test(r)) {
    return { product, variants: [], address: '', clean: r, needsEdit: false };
  }

  // Extract textarea_input_1 (variant info)
  let variantRaw = '';
  const m1 = r.match(/textarea_input_1:\s*([^\n,]+(?:,\s*[^\n,]+)*)/i);
  if (m1) variantRaw = m1[1].trim();

  // Extract textarea_input_2 (address)
  let address = '';
  const m2 = r.match(/textarea_input_2:\s*(.+)/i);
  if (m2) address = m2[1].replace(/\\n/g, ' ').trim();

  // Parse variants
  const variants = detectVariants(variantRaw, product);

  // Build clean label
  const clean = buildCleanRemarks(r, product, variants, address, variantRaw);

  return { product, variants, address, clean, needsEdit: true, variantRaw };
}

function detectVariants(text, product) {
  if (!text) {
    // Default LAD if no info given for mix orders
    return [];
  }
  const t = text.toLowerCase();

  const hasLight = /light|l\b/.test(t);
  const hasAmber = /amber|a\b/.test(t);
  const hasDark  = /dark|d\b/.test(t);
  const allColors = /all color|all colour|lad/i.test(t);
  const allAmber  = /all.?amber/i.test(t);
  const allLight  = /all.?light/i.test(t);
  const allDark   = /all.?dark/i.test(t);

  if (allColors || (hasLight && hasAmber && hasDark)) return ['L','A','D'];
  if (allAmber) return ['A','A'];
  if (allLight) return ['L','L'];
  if (allDark)  return ['D','D'];

  // 2L specific combos
  if (product === '2L') {
    const m2a1d = /2\s*amber.*1\s*dark|2a.*1d/i.test(t);
    const m1a1d = (hasAmber && hasDark && !hasLight);
    const m1l1a = (hasLight && hasAmber && !hasDark);
    const m1l1d = (hasLight && hasDark && !hasAmber);
    if (m2a1d) return ['A','A','D']; // unusual but handle
    if (m1a1d) return ['A','D'];
    if (m1l1a) return ['L','A'];
    if (m1l1d) return ['L','D'];
    if (hasAmber) return ['A','A'];
    if (hasLight) return ['L','L'];
    if (hasDark)  return ['D','D'];
  }

  // 3L combos
  if (product === '3L') {
    // 2 dark 1 amber
    if (/2\s*dark.*1\s*amber|2d.*1a/i.test(t)) return ['A','D','D'];
    if (/2\s*amber.*1\s*dark|2a.*1d/i.test(t)) return ['A','A','D'];
    if (hasLight && hasAmber && !hasDark) return ['L','L','A'];
    if (hasLight && hasDark  && !hasAmber) return ['L','D','D'];
    if (hasAmber && hasDark  && !hasLight) return ['A','A','D'];
    if (hasLight) return ['L','L','L'];
    if (hasAmber) return ['A','A','A'];
    if (hasDark)  return ['D','D','D'];
  }

  // 1L
  if (product === '1L') {
    if (hasAmber) return ['A'];
    if (hasLight) return ['L'];
    if (hasDark)  return ['D'];
  }

  return [];
}

function buildCleanRemarks(raw, product, variants, address, variantRaw) {
  // Extract quantity prefix e.g. "1 x 3L-HONEY"
  const qtyMatch = raw.match(/^(\d+\s*x\s*)/i);
  const qty = qtyMatch ? qtyMatch[1] : '1 x ';

  // Build product code
  let code = '';
  const freeBottle = /FREE 1 350mL|Set B/i.test(raw);

  if (product === '3L') {
    const label = variants.join('') || 'LAD';
    code = `${qty}3L-HONEY ${label}`;
    if (freeBottle) code += '; 1pc. 350ml. Honey';
  } else if (product === '2L') {
    const label = variants.join('') || 'LA';
    code = `${qty}2L-HONEY ${label}`;
  } else if (product === '1L') {
    const label = variants.join('') || 'A';
    code = `${qty}1L HONEY ${label}`;
  } else if (product === '350ml') {
    code = `${qty}B2T1 350ML LAD`;
  } else {
    // Keep as-is
    code = raw;
  }

  return code;
}

// ──────────────────────────────────────────────
//  RENDER
// ──────────────────────────────────────────────
function renderTable() {
  const tbody = document.getElementById('ordersBody');
  const rows = getFilteredOrders();
  document.getElementById('visibleCount').textContent = rows.length;

  if (rows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="loading">No orders match the current filter.</td></tr>';
    return;
  }

  tbody.innerHTML = rows.map(({order, idx}) => renderRow(order, idx)).join('');
  attachListeners();
}

function getFilteredOrders() {
  return allOrders
    .map((o, idx) => ({ order: o, idx }))
    .filter(({ order, idx }) => {
      const parsed = parseRemarks(order.remarks);

      // Filter by type
      if (currentFilter !== 'all' && currentFilter !== 'pending' && currentFilter !== 'edited') {
        if (parsed.product !== currentFilter) return false;
      }
      if (currentFilter === 'pending' && !parsed.needsEdit && !(editedMap[idx] === undefined)) return false;
      if (currentFilter === 'edited' && editedMap[idx] === undefined) return false;

      // Search
      if (currentSearch) {
        const q = currentSearch.toLowerCase();
        if (!order.receiver.toLowerCase().includes(q) &&
            !(order.remarks || '').toLowerCase().includes(q)) return false;
      }
      return true;
    });
}

function variantHTML(variants) {
  if (!variants.length) return '<span style="color:#ccc;font-size:.7rem">—</span>';
  const classMap = {
    'L': 'var-light', 'A': 'var-amber', 'D': 'var-dark'
  };
  const labelMap = { 'L': 'Light', 'A': 'Amber', 'D': 'Dark' };

  // Group: LAD, LA, LD, AD, or individual
  const key = variants.join('');
  if (key === 'LAD') return `<span class="var-chip var-lad">LAD</span>`;
  if (key === 'AD')  return `<span class="var-chip var-ad">AD</span>`;
  if (key === 'LA')  return `<span class="var-chip var-la">LA</span>`;
  if (key === 'LD')  return `<span class="var-chip var-ld">LD</span>`;

  return variants.map(v =>
    `<span class="var-chip ${classMap[v]||'var-light'}">${labelMap[v]||v}</span>`
  ).join('');
}

function typeBadge(product) {
  const m = {
    '3L': ['type-3l','3 Liter'],
    '2L': ['type-2l','2 Liter'],
    '1L': ['type-1l','1 Liter'],
    '350ml': ['type-350','350 mL'],
    'other': ['type-other','Other'],
  };
  const [cls, label] = m[product] || ['type-other', product];
  return `<span class="type-badge ${cls}">${label}</span>`;
}

function renderRow(order, idx) {
  const parsed   = parseRemarks(order.remarks);
  const current  = editedMap[idx] !== undefined ? editedMap[idx] : parsed.clean;
  const isDirty  = editedMap[idx] !== undefined && editedMap[idx] !== parsed.clean;
  const rowClass = isDirty ? 'row-dirty' : '';

  // Quick preset buttons based on product
  const quickPresets = buildPresets(parsed.product, idx, order.remarks);

  const rawDisplay = (order.remarks || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  return `
  <tr class="${rowClass}" data-idx="${idx}" data-product="${parsed.product}" data-needs="${parsed.needsEdit?1:0}">
    <td class="col-num">${idx+1}</td>
    <td class="col-name">
      <div class="receiver-name">${esc(order.receiver)}</div>
      <div class="receiver-phone">${esc(order.phone||'')}</div>
    </td>
    <td class="col-type">
      ${typeBadge(parsed.product)}
      <div style="margin-top:5px">${variantHTML(parsed.variants)}</div>
      ${parsed.variantRaw ? `<div class="address-hint">"${esc(parsed.variantRaw)}"</div>` : ''}
    </td>
    <td class="col-raw">
      <div class="raw-text" onclick="toggleRaw(this)">${rawDisplay}</div>
    </td>
    <td class="col-edit">
      <div class="clean-remarks-wrap">
        <textarea class="clean-remarks${isDirty?' modified':''}" 
          data-idx="${idx}" 
          rows="2"
          onchange="onEdit(${idx}, this.value)"
          oninput="onInput(${idx}, this)"
        >${esc(current)}</textarea>
        ${parsed.address ? `<div class="address-hint">📍 ${esc(parsed.address)}</div>` : ''}
        <div class="quick-btns">${quickPresets}</div>
      </div>
    </td>
    <td class="col-act">
      <button class="btn-save" onclick="saveRow(${idx})" ${isDirty?'':'disabled'} id="savebtn-${idx}">
        Save
      </button>
      <div class="saved-indicator" id="saved-${idx}">✓ Saved</div>
    </td>
  </tr>`;
}

function buildPresets(product, idx, raw) {
  const freeBottle = /FREE 1 350mL|Set B/i.test(raw||'');
  const qtyMatch = (raw||'').match(/^(\d+\s*x\s*)/i);
  const qty = qtyMatch ? qtyMatch[1].trim() : '1 x';

  let btns = [];
  if (product === '3L') {
    const suf = freeBottle ? '; 1pc. 350ml. Honey' : '';
    btns = [
      [`${qty} 3L-HONEY LAD${suf}`, 'LAD'],
      [`${qty} 3L-HONEY LLL${suf}`, 'LLL'],
      [`${qty} 3L-HONEY AAA${suf}`, 'AAA'],
      [`${qty} 3L-HONEY DDD${suf}`, 'DDD'],
      [`${qty} 3L-HONEY LAA${suf}`, 'LAA'],
      [`${qty} 3L-HONEY LLD${suf}`, 'LLD'],
      [`${qty} 3L-HONEY ADD${suf}`, 'ADD'],
      [`${qty} 3L-HONEY AAD${suf}`, 'AAD'],
    ];
  } else if (product === '2L') {
    btns = [
      [`${qty} 2L-HONEY LA`, 'LA'],
      [`${qty} 2L-HONEY LD`, 'LD'],
      [`${qty} 2L-HONEY AD`, 'AD'],
      [`${qty} 2L-HONEY LL`, 'LL'],
      [`${qty} 2L-HONEY AA`, 'AA'],
      [`${qty} 2L-HONEY DD`, 'DD'],
    ];
  } else if (product === '1L') {
    btns = [
      [`${qty} 1L HONEY L`, 'L'],
      [`${qty} 1L HONEY A`, 'A'],
      [`${qty} 1L HONEY D`, 'D'],
    ];
  } else if (product === '350ml') {
    btns = [[`${qty} B2T1 350ML LAD`, 'LAD']];
  }

  return btns.map(([val, lbl]) =>
    `<button class="qb" onclick="applyPreset(${idx}, ${JSON.stringify(val)})">${lbl}</button>`
  ).join('');
}

function esc(s) {
  return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ──────────────────────────────────────────────
//  INTERACTIONS
// ──────────────────────────────────────────────
function attachListeners() {}

function onInput(idx, textarea) {
  textarea.classList.add('modified');
  textarea.classList.remove('saved');
  const btn = document.getElementById('savebtn-' + idx);
  if (btn) btn.disabled = false;
}

function onEdit(idx, val) {
  editedMap[idx] = val;
  updateStats();
}

function applyPreset(idx, val) {
  const ta = document.querySelector(`textarea[data-idx="${idx}"]`);
  if (ta) {
    ta.value = val;
    ta.classList.add('modified');
    ta.classList.remove('saved');
    editedMap[idx] = val;
    const btn = document.getElementById('savebtn-' + idx);
    if (btn) btn.disabled = false;
    updateStats();
  }
}

function saveRow(idx) {
  const ta = document.querySelector(`textarea[data-idx="${idx}"]`);
  if (!ta) return;
  const val = ta.value;
  editedMap[idx] = val;

  // Visual feedback
  ta.classList.remove('modified');
  ta.classList.add('saved');
  const btn = document.getElementById('savebtn-' + idx);
  if (btn) btn.disabled = true;
  const ind = document.getElementById('saved-' + idx);
  if (ind) { ind.style.display = 'block'; setTimeout(()=>{ ind.style.display='none'; }, 2000); }

  // Row class
  const row = document.querySelector(`tr[data-idx="${idx}"]`);
  if (row) { row.classList.remove('row-dirty'); row.classList.add('row-saved'); }

  updateStats();

  // Persist to server
  fetch('api.php?action=save_edit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idx, remarks: val })
  });
}

function toggleRaw(el) { el.classList.toggle('expanded'); }

// ──────────────────────────────────────────────
//  FILTER & SEARCH
// ──────────────────────────────────────────────
function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderTable();
}

function doSearch(q) {
  currentSearch = q;
  renderTable();
}

// ──────────────────────────────────────────────
//  STATS
// ──────────────────────────────────────────────
function updateStats() {
  const total   = allOrders.length;
  const edited  = Object.keys(editedMap).length;
  const pending = allOrders.filter((o, i) => {
    const p = parseRemarks(o.remarks);
    return p.needsEdit && editedMap[i] === undefined;
  }).length;

  document.getElementById('statTotal').textContent  = total;
  document.getElementById('statEdited').textContent = edited;
  document.getElementById('statPending').textContent = pending;
}

// ──────────────────────────────────────────────
//  EXPORT
// ──────────────────────────────────────────────
async function exportExcel() {
  // Collect all current remarks (edited or original clean)
  const edits = allOrders.map((o, idx) => {
    if (editedMap[idx] !== undefined) return editedMap[idx];
    const parsed = parseRemarks(o.remarks);
    return parsed.clean || o.remarks || '';
  });

  const res = await fetch('api.php?action=export', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ edits })
  });

  if (res.ok) {
    const blob = await res.blob();
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'orders_updated_' + new Date().toISOString().slice(0,10) + '.xlsx';
    a.click();
    URL.revokeObjectURL(url);
  } else {
    const d = await res.json();
    alert('Export failed: ' + (d.error || 'Unknown error'));
  }
}

// ──────────────────────────────────────────────
//  INIT
// ──────────────────────────────────────────────
loadOrders();
</script>
</body>
</html>
