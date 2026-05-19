<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
rts_require_roles([RTS_ROLE_ADMIN], 'login.php');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/deliveries_review_nav.php';
require_once __DIR__ . '/includes/admin_nav.php';
require_once __DIR__ . '/includes/rts_products.php';
$deliveryNavBadge = '';
try {
    $deliveryNavBadge = rts_delivery_review_nav_badge_html(rts_pdo());
} catch (Throwable) {
}

$pageTitle = 'Record RTS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root {
      --bg: #fffdf6;
      --panel: #ffffff;
      --border: #e8dcc5;
      --text: #3f2f1e;
      --muted: #7e6a4b;
      --accent: #d99200;
      --warn: #d08a00;
      --danger: #c65a4a;
      --ok: #34a853;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.45;
      min-height: 100vh;
      opacity: 1;
      transition: opacity .22s ease;
    }
    body.page-enter { animation: pageIn .28s ease both; }
    body.page-exit { opacity:0; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.45;
      min-height: 100vh;
      display:flex;
    }
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
    main {
      flex:1;
      max-width: 720px;
      margin: 0 auto;
      padding: 1.25rem;
      min-height: calc(100vh - 2.5rem);
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 0.55rem 1rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      font-size: 0.95rem;
      cursor: pointer;
    }
    .btn:hover { border-color: var(--muted); }
    .btn-primary { background: var(--accent); border-color: var(--accent); color: #061018; font-weight: 600; }
    .btn-primary:hover { filter: brightness(1.05); }
    .btn-ghost { background: transparent; }
    .btn-danger { border-color: var(--danger); color: var(--danger); }
    .card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1rem 1.1rem;
      margin-bottom: 1rem;
    }
    .intro-card {
      width: 100%;
      max-width: 560px;
      align-self: center;
    }
    label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 0.25rem; }
    select, input[type="text"], input[type="date"], input[type="number"] {
      width: 100%;
      padding: 0.5rem 0.6rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: #fffdfa;
      color: var(--text);
      font-size: 0.95rem;
    }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    @media (max-width: 560px) { .row { grid-template-columns: 1fr; } }
    .hidden { display: none !important; }
    /* Mobile browsers block or mis-handle programmatic .click() on display:none file inputs;
       overlay the input on a visible label so the tap activates the picker/camera directly. */
    label.file-pick-label {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 0.55rem 1rem;
      border-radius: 8px;
      border: 1px solid var(--border);
      margin-bottom: 0;
      font-size: 0.95rem;
      color: var(--text);
      font-weight: inherit;
      cursor: pointer;
      overflow: hidden;
      -webkit-tap-highlight-color: transparent;
    }
    label.file-pick-label.btn-ghost { background: transparent; }
    label.file-pick-label:hover { border-color: var(--muted); }
    label.file-pick-label input[type="file"] {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      opacity: 0.001;
      cursor: pointer;
      font-size: 1rem;
      z-index: 2;
    }
    #reader { width: 100%; border-radius: 10px; overflow: hidden; border: 1px solid var(--border); }
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(75,58,29,0.28);
      display: flex; align-items: flex-start; justify-content: center;
      padding: 1rem; z-index: 50; overflow-y: auto;
      animation: fadeIn .18s ease both;
    }
    .modal-backdrop.damaged-delivery-tone {
      background: rgba(110, 38, 38, 0.34);
    }
    .modal-backdrop.damaged-delivery-tone .modal {
      border-color: #d8a8a8;
      box-shadow: 0 10px 36px rgba(90, 28, 28, 0.2);
    }
    .modal {
      width: min(560px, 100%);
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.1rem 1.2rem 1.25rem;
      margin: 2vh 0 4vh;
      animation: popIn .22s ease both;
    }
    .modal h2 { margin: 0 0 0.75rem; font-size: 1.05rem; }
    .steps { font-size: 0.85rem; color: var(--muted); margin-bottom: 0.75rem; }
    .line-chip {
      display: flex; justify-content: space-between; align-items: center;
      padding: 0.45rem 0.6rem; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 0.4rem;
      font-size: 0.88rem; flex-wrap: wrap; gap: 0.3rem;
    }
    .line-chip.is-bonus {
      margin-left: 1.2rem; border-left: 3px solid var(--accent); background: #fffbf0;
    }
    .line-chip span { flex: 1; min-width: 0; word-break: break-word; }
    .line-form-card { padding: 0.85rem 0.9rem; }
    .line-form-row { margin-bottom: 0.6rem; }
    .line-form-row:last-child { margin-bottom: 0; }
    .line-form-row.hidden { display: none !important; }
    .line-form-row label { display: block; font-size: 0.78rem; color: var(--muted); margin-bottom: 0.2rem; }
    .line-form-row select,
    .line-form-row input[type="number"] {
      width: 100%; padding: 0.5rem 0.55rem; border-radius: 8px; border: 1px solid var(--border);
      font-size: 0.95rem; font-family: inherit; min-height: 44px; background: #fffdfa;
    }
    .line-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
    .line-form-subcard {
      background: #fff7ea; border: 1px dashed var(--border); border-radius: 10px; padding: 0.6rem 0.7rem;
    }
    .line-form-sublabel { display: block; font-size: 0.76rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem; }
    .line-form-actions { display: flex; gap: 0.5rem; margin-top: 0.65rem; flex-wrap: wrap; }
    .line-form-actions .btn { min-height: 44px; flex: 1; }
    .variant-rows-label { font-size: 0.78rem; color: var(--muted); margin: 0.5rem 0 0.35rem; display: block; }
    .variant-row {
      display: grid; grid-template-columns: 1fr 5.5rem auto; gap: 0.45rem; align-items: end;
      margin-bottom: 0.45rem;
    }
    .variant-row select, .variant-row input[type="number"] {
      width: 100%; padding: 0.5rem 0.55rem; border-radius: 8px; border: 1px solid var(--border);
      font-size: 0.95rem; min-height: 44px; background: #fffdfa;
    }
    .variant-row .btn-remove-variant {
      min-height: 44px; padding: 0.4rem 0.55rem; font-size: 0.82rem;
    }
    .btn-add-variant {
      margin-top: 0.35rem; font-size: 0.85rem; padding: 0.45rem 0.65rem; min-height: 40px;
    }
    @media (max-width: 560px) {
      .line-form-grid { grid-template-columns: 1fr; }
      .line-form-actions { flex-direction: column; }
      .variant-row { grid-template-columns: 1fr 1fr; }
      .variant-row .btn-remove-variant { grid-column: 1 / -1; }
    }
    .banner-warn {
      background: rgba(245, 166, 35, 0.12);
      border: 1px solid var(--warn);
      color: #ffd89a;
      padding: 0.65rem 0.75rem;
      border-radius: 10px;
      margin-bottom: 0.75rem;
      font-size: 0.9rem;
    }
    .banner-danger {
      background: rgba(232, 93, 93, 0.1);
      border: 1px solid var(--danger);
      color: #ffb4b4;
      padding: 0.65rem 0.75rem;
      border-radius: 10px;
      margin-bottom: 0.75rem;
      font-size: 0.9rem;
    }
    .toolbar { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
    .mono { font-family: ui-monospace, Consolas, monospace; font-size: 0.88rem; }
    dl.summary dt { color: var(--muted); font-size: 0.78rem; margin-top: 0.5rem; }
    dl.summary dd { margin: 0.1rem 0 0; }
    .section-title { margin: 1rem 0 0.5rem; font-size: 0.92rem; color: var(--muted); }
    .compare-strip { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.35rem; }
    @media (max-width: 520px) { .compare-strip { grid-template-columns: 1fr; } }
    .compare-box h4 { margin: 0 0 0.35rem; font-size: 0.76rem; color: var(--muted); font-weight: 650; text-transform: uppercase; letter-spacing: 0.03em; }
    .compare-box img { width: 100%; max-height: 260px; object-fit: contain; border-radius: 8px; border: 1px solid var(--border); background: #faf7f0; }
    .compare-box video { width: 100%; max-height: 260px; border-radius: 8px; border: 1px solid var(--border); background: #111; }
    .doc-attach-grid { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 0.45rem; align-items: flex-start; }
    .doc-attach-tile { border: 1px solid var(--border); border-radius: 10px; padding: 0.45rem; background: #fffdfa; flex: 0 1 auto; max-width: min(220px, 100%); }
    .doc-attach-tile img { max-width: 100%; max-height: 140px; object-fit: contain; display: block; border-radius: 6px; background: #faf7f0; }
    .doc-attach-tile video { max-width: 100%; max-height: 160px; display: block; border-radius: 6px; background: #111; }
    .compare-media-stack { display: flex; flex-direction: column; gap: 0.5rem; min-height: 0; }
    .compare-media-stack .doc-attach-tile { max-width: 100%; }
    label .req { color: var(--danger); font-weight: 600; }
    select:disabled, input:disabled { opacity: 0.65; cursor: not-allowed; background: #f5f0e6; }
    #btnSave:disabled { opacity: 0.45; cursor: not-allowed; }
    #formValidationBanner { font-size: 0.88rem; margin: 0 0 0.75rem; }
    .success-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 80;
    }
    .success-modal {
      background: var(--panel);
      border-radius: 16px;
      border: 1px solid var(--border);
      padding: 1.2rem 1.4rem 1.3rem;
      text-align: center;
      width: min(320px, 90%);
      box-shadow: 0 18px 45px rgba(0,0,0,0.5);
    }
    .success-check {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      border: 3px solid rgba(52,199,89,0.25);
      margin: 0 auto 0.6rem;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .success-check::before {
      content: '';
      width: 28px;
      height: 14px;
      border-left: 3px solid var(--ok);
      border-bottom: 3px solid var(--ok);
      transform: rotate(-45deg) scale(0);
      transform-origin: center;
      animation: check-draw 320ms ease-out forwards 120ms;
    }
    .success-check::after {
      content: '';
      position: absolute;
      inset: -1px;
      border-radius: 50%;
      border: 3px solid rgba(52,199,89,0.45);
      transform: scale(0.4);
      opacity: 0;
      animation: check-pulse 480ms ease-out forwards;
    }
    @keyframes check-draw {
      from { transform: rotate(-45deg) scale(0); }
      to { transform: rotate(-45deg) scale(1); }
    }
    @keyframes check-pulse {
      0% { transform: scale(0.4); opacity: 0.7; }
      100% { transform: scale(1.1); opacity: 0; }
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
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
    @keyframes popIn { from { opacity:0; transform: translateY(10px) scale(.98); } to { opacity:1; transform: translateY(0) scale(1); } }
    @keyframes pageIn {
      from { opacity:0; }
      to { opacity:1; }
    }
    @media (max-width: 720px) {
      .sidebar { display: none !important; }
      .mobile-topbar { display: flex; }
      main { padding-top: 80px; min-height: calc(100vh - 80px); }
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
      <?php rts_admin_nav_links('record', '', $deliveryNavBadge); ?>
    </nav>
  </div>
  <aside class="sidebar">
    <div>
      <img src="assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:42px;width:auto;max-width:100%;border-radius:8px;margin-bottom:0.5rem;" onerror="this.style.display='none'">
      <h1>RTS system</h1>
      <p style="margin:0.25rem 0 0;font-size:0.78rem;color:var(--muted);">Returns tracking &amp; reporting</p>
    </div>
    <nav class="nav-links">
      <?php rts_admin_nav_links('record', '', $deliveryNavBadge); ?>
    </nav>
  </aside>
  <main>
    <div class="card intro-card">
      <p style="margin:0 0 0.75rem;color:var(--muted);font-size:0.92rem;">
        Scan the courier QR for the tracking number: use <strong>Take photo of QR</strong> or <strong>Gallery / files</strong> (works on Wi‑Fi at <code>http://192.168…</code>), the <strong>live preview</strong> only after <strong>HTTPS</strong> or on <strong>localhost</strong>, or enter the number manually. Then fill parcel details — honey uses pack size + variant; other products use quantity only.
      </p>
      <button type="button" class="btn btn-primary" id="btnStart">Add RTS</button>
    </div>

    <div id="scanPanel" class="card hidden">
      <div id="scanHttpBanner" class="hidden" style="padding:0.65rem 0.75rem;background:#fff7ea;border:1px solid var(--border);border-radius:10px;margin-bottom:0.85rem;font-size:0.88rem;color:var(--text);line-height:1.4;">
        <strong>Live QR preview is turned off on this link.</strong> Phones and Chrome only allow the embedded live camera on <strong>HTTPS</strong> or <strong>localhost</strong>, not on plain <code>http://192.168…</code> — that is a browser rule, not a bug in this app.
        <p style="margin:0.55rem 0 0;color:var(--muted);">Use <strong>Take photo of QR</strong> or <strong>Gallery / files</strong> — the photo is sent to this PC and decoded with PHP (<a href="https://github.com/chillerlan/php-qrcode" target="_blank" rel="noopener">chillerlan/php-qrcode</a>), which works on plain HTTP. Or type the tracking number. The embedded <strong>live</strong> preview still needs <strong>HTTPS</strong> or <strong>localhost</strong>.</p>
      </div>

      <div id="scanLiveCameraRow" class="row" style="margin-bottom:0.75rem;">
        <div>
          <label for="camSelect">Camera for scanner</label>
          <select id="camSelect"></select>
        </div>
        <div style="display:flex;align-items:flex-end;">
          <button type="button" class="btn btn-ghost" id="btnRefreshCams" style="width:100%;">Refresh cameras</button>
        </div>
      </div>
      <div class="row" style="margin-bottom:0.75rem;">
        <div>
          <label for="manualTracking">Tracking number (manual)</label>
          <input type="text" id="manualTracking" placeholder="Paste or type if scan fails" autocomplete="off">
        </div>
        <div style="display:flex;align-items:flex-end;">
          <button type="button" class="btn btn-primary" id="btnUseManual" style="width:100%;">Use manual entry</button>
        </div>
      </div>
      <div class="row" style="margin-bottom:0.75rem;">
        <div style="display:flex;align-items:flex-end;gap:0.5rem;flex-wrap:wrap;">
          <label class="btn btn-ghost file-pick-label" style="flex:1;min-width:10rem;">
            <input type="file" id="qrFileInput" accept="image/*" aria-label="Choose a photo of the QR from gallery or files">
            Gallery / files
          </label>
          <label class="btn btn-ghost file-pick-label" style="flex:1;min-width:10rem;">
            <input type="file" id="qrCameraInput" accept="image/*" capture="environment" aria-label="Take a photo of the QR with the camera">
            Take photo of QR
          </label>
          <button type="button" class="btn btn-ghost" id="btnQrFromPaste" title="Paste a screenshot from clipboard" style="flex:1;min-width:10rem;">
            Paste image (clipboard)
          </button>
        </div>
      </div>
      <div id="scanLivePreviewSection">
        <label>Live QR preview</label>
        <div id="reader"></div>
      </div>
      <div id="qrFileReader" aria-hidden="true" style="position:fixed;width:320px;height:320px;left:-10000px;top:0;overflow:hidden;"></div>
      <div class="toolbar">
        <button type="button" class="btn btn-ghost" id="btnStopScan">Stop camera</button>
        <button type="button" class="btn btn-ghost" id="btnCancelScan">Cancel</button>
      </div>
    </div>
  </main>

  <!-- New case modal -->
  <div id="modalBackdrop" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal" id="modalNew">
      <h2 id="modalTitle">New RTS</h2>
      <div class="steps" id="modalStepLabel">Fill out details and save in one screen</div>
      <div style="margin:0 0 0.75rem;">
        <label for="dispTracking">Tracking number</label>
        <input type="text" id="dispTracking" class="mono" autocomplete="off" spellcheck="false" placeholder="Paste or correct tracking number">
        <p class="muted" style="margin:0.35rem 0 0;font-size:0.78rem;">Wrong QR? Edit the number here before saving.</p>
        <p id="trackingEditWarn" class="banner-warn hidden" style="margin:0.5rem 0 0;font-size:0.82rem;" role="status"></p>
      </div>
      <p id="detectedCourierHint" class="hidden" style="margin:0 0 0.65rem;font-size:0.84rem;color:var(--muted);"></p>
      <div id="formValidationBanner" class="banner-warn hidden" role="status"></div>
      <div class="row">
        <div>
          <label for="shop">Shop <span class="req" aria-hidden="true">*</span></label>
          <select id="shop" required>
            <option value="">— Select shop —</option>
            <option value="shopee">Shopee</option>
            <option value="lazada">Lazada</option>
            <option value="tiktok">TikTok</option>
            <option value="vip">VIP (direct)</option>
          </select>
        </div>
        <div>
          <label for="courierFinal">Courier <span class="req" aria-hidden="true">*</span></label>
          <select id="courierFinal" disabled>
            <option value="">Select shop first</option>
          </select>
        </div>
      </div>
      <div id="vipRow" class="row hidden" style="margin-top:0.75rem;">
        <div style="grid-column:1/-1">
          <label for="vipSub">VIP source (optional)</label>
          <select id="vipSub">
            <option value="">— Not specified —</option>
            <option value="website">Website</option>
            <option value="facebook">Facebook</option>
          </select>
        </div>
      </div>
      <div class="row" style="margin-top:0.75rem;">
        <div>
          <label for="dateDelivered">Date delivered <span class="req" aria-hidden="true">*</span></label>
          <input type="date" id="dateDelivered" required>
        </div>
        <div>
          <label for="dateOpened">Date opened / recorded <span class="req" aria-hidden="true">*</span></label>
          <input type="date" id="dateOpened" required>
        </div>
      </div>
      <div style="margin-top:0.75rem;">
        <label>Opening / damage proof (optional)</label>
        <p style="margin:0.25rem 0 0;font-size:0.82rem;color:var(--muted);">Upload <strong>multiple photos</strong> and/or a <strong>video</strong> (e.g. unboxing for courier claims). Images up to ~12MB each; MP4, WebM, or MOV up to ~120MB. On a phone, tap the button area to pick files or use the camera for a still photo.</p>
        <div class="toolbar" style="margin-top:0.4rem;">
          <label class="btn btn-ghost file-pick-label">
            <input type="file" id="docFileInput" accept="image/*,video/*" multiple aria-label="Upload documentation photos or videos">
            Upload files
          </label>
          <label class="btn btn-ghost file-pick-label">
            <input type="file" id="docCameraInput" accept="image/*" capture="environment" aria-label="Take documentation photo with camera">
            Use camera (photo)
          </label>
          <span id="docFileName" class="mono" style="color:var(--muted);"></span>
        </div>
        <div id="docAttachmentsList" class="doc-attach-grid"></div>
      </div>

      <div id="damagedBlock" class="card" style="margin-top:0.85rem;background:#fffaf8;border-color:#ead6ce;">
        <label style="display:flex;align-items:flex-start;gap:0.5rem;cursor:pointer;font-weight:600;">
          <input type="checkbox" id="damagedCheck" style="margin-top:0.2rem;">
          <span>Parcel is <strong>damaged</strong> (record for RTS)</span>
        </label>
        <div id="damagedReasonRow" class="hidden" style="margin-top:0.65rem;">
          <label for="damagedReason">Damage reason</label>
          <select id="damagedReason" style="width:100%;max-width:320px;margin-top:0.25rem;padding:0.45rem;border-radius:8px;border:1px solid var(--border);background:#fffdfa;">
            <option value="">— Select reason —</option>
            <option value="leak">Leak</option>
            <option value="item_replaced">Item replaced</option>
            <option value="packaging_replaced">Packaging replaced</option>
          </select>
        </div>
        <div id="deliveryCompareBlock" class="hidden" style="margin-top:0.85rem;">
          <p class="muted" style="margin:0 0 0.5rem;font-size:0.82rem;"><strong>Before → after</strong> — pre-delivery (Deliveries) vs RTS documentation.</p>
          <div class="compare-strip">
            <div class="compare-box">
              <h4>Before delivery</h4>
              <img id="deliveryCompareImg" alt="Pre-delivery" class="hidden">
              <div id="deliveryComparePlaceholder" class="muted" style="padding:0.6rem;font-size:0.84rem;border:1px dashed var(--border);border-radius:8px;">No matching Deliveries photo for this tracking.</div>
              <div class="toolbar" style="margin-top:0.35rem;">
                <a id="deliveryCompareDl" class="btn btn-ghost hidden" href="#" download>Download</a>
                <a id="deliveryCompareOpen" class="btn btn-ghost hidden" href="#" target="_blank" rel="noopener">Open</a>
              </div>
            </div>
            <div class="compare-box">
              <h4>RTS (return)</h4>
              <div id="rtsCompareAttachments" class="compare-media-stack"></div>
              <div id="rtsDocComparePlaceholder" class="muted" style="padding:0.6rem;font-size:0.84rem;border:1px dashed var(--border);border-radius:8px;">Add RTS photos or video above (optional).</div>
            </div>
          </div>
        </div>
        <p id="damagedGateMsg" class="banner-danger hidden" style="margin-top:0.75rem;">Damaged parcels require a pre-delivery Deliveries photo with the same tracking. Upload in Deliveries first, or uncheck Damaged.</p>
      </div>

      <p class="section-title">Products <span class="req" aria-hidden="true">*</span></p>
      <p style="margin:0 0 0.5rem;color:var(--muted);font-size:0.9rem;">Add at least one product. Fields change based on selection.</p>
      <div id="pendingLines"></div>
      <div class="card line-form-card" style="background:#fff9ef;margin-top:0.75rem;">
        <div class="line-form-row">
          <label for="lineProductType">Product</label>
          <select id="lineProductType">
            <?php foreach (RTS_PRODUCT_TYPES as $pt): ?>
            <option value="<?= $pt ?>"><?= htmlspecialchars(RTS_PRODUCT_LABELS[$pt], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="line-form-row" id="wrapHoneyMain">
          <label for="honeySize">Size</label>
              <select id="honeySize">
                <?php foreach (RTS_HONEY_SIZES as $s): ?>
                <option value="<?= $s ?>"><?= htmlspecialchars(RTS_HONEY_SIZE_LABELS[$s], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
          <span class="variant-rows-label">Variants — enter qty for each (e.g. 2 Liter: 1 Amber + 1 Dark)</span>
          <div id="honeyVariantList" class="honey-variant-list" data-field="honey"></div>
          <button type="button" class="btn btn-ghost btn-add-variant" id="btnAddHoneyVariant">+ Add variant row</button>
        </div>
        <div class="line-form-row" id="wrapComboOther">
          <div class="line-form-subcard">
            <span class="line-form-sublabel">Paired with:</span>
            <label for="comboProduct">Product</label>
            <select id="comboProduct">
              <?php foreach (RTS_COMBO_PRODUCTS as $cp): ?>
              <option value="<?= $cp ?>"><?= htmlspecialchars(RTS_COMBO_PRODUCT_LABELS[$cp], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <label for="comboQty">Quantity</label>
            <input type="number" id="comboQty" min="1" step="1" value="1">
          </div>
        </div>
        <div class="line-form-row" id="wrapComboHoney">
          <div class="line-form-subcard">
            <span class="line-form-sublabel">Paired honey:</span>
            <label for="comboHoneySize">Size</label>
            <select id="comboHoneySize">
              <?php foreach (RTS_HONEY_SIZES as $s): ?>
              <option value="<?= $s ?>"><?= htmlspecialchars(RTS_HONEY_SIZE_LABELS[$s], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <span class="variant-rows-label">Honey variants (qty per type)</span>
            <div id="comboHoneyVariantList" class="honey-variant-list" data-field="comboHoney"></div>
            <button type="button" class="btn btn-ghost btn-add-variant" id="btnAddComboHoneyVariant">+ Add variant row</button>
          </div>
        </div>
        <div class="line-form-row" id="wrapLineQty">
          <label for="lineQty">Quantity</label>
          <input type="number" id="lineQty" min="1" step="1" value="1">
        </div>
        <div class="line-form-actions">
          <button type="button" class="btn btn-primary" id="btnAddLine">Add to list</button>
          <button type="button" class="btn btn-ghost" id="btnAddBonus">+ Bonus item</button>
        </div>
      </div>

      <p class="section-title">Live summary</p>
      <dl class="summary" id="confirmSummary"></dl>
      <div class="toolbar" style="justify-content:space-between;margin-top:0.75rem;">
        <button type="button" class="btn btn-ghost" id="btnModalCancelNew">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnSave" disabled>Save RTS</button>
      </div>
    </div>
  </div>

  <!-- Duplicate modal -->
  <div id="dupBackdrop" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal">
      <h2>Tracking already exists</h2>
      <div class="banner-warn">This tracking number is already in the database. Review the record below. You can close or add more product lines to this same return.</div>
      <p class="mono" style="margin:0 0 0.5rem;">Tracking: <strong id="dupTracking"></strong></p>
      <div id="dupDetails" class="card" style="background:#fff9ef;"></div>
      <div class="toolbar" style="justify-content:space-between;margin-top:0.75rem;">
        <button type="button" class="btn btn-ghost" id="dupClose">Close</button>
        <button type="button" class="btn btn-primary" id="dupAppend">Add more products…</button>
      </div>
    </div>
  </div>

  <!-- Append-only line flow (after duplicate) -->
  <div id="appendBackdrop" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal">
      <h2>Add products to existing RTS</h2>
      <p class="mono" style="margin:0 0 0.75rem;">Tracking: <strong id="appendTracking"></strong></p>
      <div id="appendStepLines">
        <div id="appendPendingLines"></div>
        <div class="card line-form-card" style="background:#fff9ef;">
          <div class="line-form-row">
            <label for="a_lineProductType">Product</label>
            <select id="a_lineProductType">
              <?php foreach (RTS_PRODUCT_TYPES as $pt): ?>
              <option value="<?= $pt ?>"><?= htmlspecialchars(RTS_PRODUCT_LABELS[$pt], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="line-form-row" id="a_wrapHoneyMain">
            <label for="a_honeySize">Size</label>
                <select id="a_honeySize">
                  <?php foreach (RTS_HONEY_SIZES as $s): ?>
                  <option value="<?= $s ?>"><?= htmlspecialchars(RTS_HONEY_SIZE_LABELS[$s], ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
            <span class="variant-rows-label">Variants — enter qty for each</span>
            <div id="a_honeyVariantList" class="honey-variant-list" data-field="honey"></div>
            <button type="button" class="btn btn-ghost btn-add-variant" id="a_btnAddHoneyVariant">+ Add variant row</button>
          </div>
          <div class="line-form-row" id="a_wrapComboOther">
            <div class="line-form-subcard">
              <span class="line-form-sublabel">Paired with:</span>
              <label for="a_comboProduct">Product</label>
              <select id="a_comboProduct">
                <?php foreach (RTS_COMBO_PRODUCTS as $cp): ?>
                <option value="<?= $cp ?>"><?= htmlspecialchars(RTS_COMBO_PRODUCT_LABELS[$cp], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <label for="a_comboQty">Quantity</label>
              <input type="number" id="a_comboQty" min="1" step="1" value="1">
            </div>
          </div>
          <div class="line-form-row" id="a_wrapComboHoney">
            <div class="line-form-subcard">
              <span class="line-form-sublabel">Paired honey:</span>
              <label for="a_comboHoneySize">Size</label>
              <select id="a_comboHoneySize">
                <?php foreach (RTS_HONEY_SIZES as $s): ?>
                <option value="<?= $s ?>"><?= htmlspecialchars(RTS_HONEY_SIZE_LABELS[$s], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <span class="variant-rows-label">Honey variants (qty per type)</span>
              <div id="a_comboHoneyVariantList" class="honey-variant-list" data-field="comboHoney"></div>
              <button type="button" class="btn btn-ghost btn-add-variant" id="a_btnAddComboHoneyVariant">+ Add variant row</button>
            </div>
          </div>
          <div class="line-form-row" id="a_wrapLineQty">
            <label for="a_lineQty">Quantity</label>
            <input type="number" id="a_lineQty" min="1" step="1" value="1">
          </div>
          <div class="line-form-actions">
            <button type="button" class="btn btn-primary" id="a_btnAddLine">Add to list</button>
            <button type="button" class="btn btn-ghost" id="a_btnAddBonus">+ Bonus item</button>
          </div>
        </div>
        <div class="toolbar" style="justify-content:flex-end;margin-top:0.75rem;">
          <button type="button" class="btn btn-ghost" id="a_btnCancel">Cancel</button>
          <button type="button" class="btn btn-primary" id="a_btnSave" disabled>Save new lines</button>
        </div>
      </div>
    </div>
  </div>

  <div id="toast" class="hidden" style="position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);background:var(--panel);border:1px solid var(--border);padding:0.6rem 1rem;border-radius:10px;z-index:60;"></div>
  <div id="successBackdrop" class="success-backdrop hidden" aria-hidden="true">
    <div class="success-modal">
      <div class="success-check"></div>
      <p style="margin:0 0 0.25rem;font-weight:600;">Saved successfully</p>
      <p style="margin:0;font-size:0.85rem;color:var(--muted);">RTS details have been recorded.</p>
    </div>
  </div>

  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script>
(function () {
  const api = 'api/case.php';
  const uploadApi = 'api/upload_documentation.php';
  const qrDecodeApi = 'api/scan_qr_image.php';
  const deliveryMatchApi = 'api/delivery_match_tracking.php';
  let html5Qr = null;
  let currentTracking = '';
  let pendingLines = [];
  let appendLines = [];
  const RTS_ATTACHMENTS_MAX = 24;
  let documentationAttachments = [];
  /** @type {null | { ok?: boolean, match?: boolean, item_id?: number, photo_filename?: string, file_url?: string }} */
  let deliveryMatch = null;
  /** @type {null | string} */
  let detectedCourierHint = null;

  const SHOP_COURIERS = {
    shopee: ['jnt', 'spx', 'flash'],
    lazada: ['jnt', 'flash'],
    tiktok: ['jnt', 'flash'],
    vip: ['jnt', 'spx']
  };
  const COURIER_LABELS = { jnt: 'J&T', spx: 'SPX', flash: 'Flash' };

  const $ = (id) => document.getElementById(id);
  const toast = (msg, isErr) => {
    const t = $('toast');
    t.textContent = msg;
    t.classList.remove('hidden');
    t.style.borderColor = isErr ? 'var(--danger)' : 'var(--border)';
    clearTimeout(toast._tm);
    toast._tm = setTimeout(() => t.classList.add('hidden'), 3200);
  };

  function showSuccess() {
    const el = $('successBackdrop');
    if (!el) return;
    el.classList.remove('hidden');
    el.setAttribute('aria-hidden', 'false');
    clearTimeout(showSuccess._tm);
    showSuccess._tm = setTimeout(() => {
      el.classList.add('hidden');
      el.setAttribute('aria-hidden', 'true');
    }, 1600);
  }

  function todayISO() {
    const d = new Date();
    const z = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + z(d.getMonth() + 1) + '-' + z(d.getDate());
  }

  function normalizeTracking(text) {
    return String(text || '').trim();
  }

  function clearQrFileInputs() {
    $('qrFileInput').value = '';
    $('qrCameraInput').value = '';
  }

  /**
   * Embedded live QR scanning uses getUserMedia, which only works in a secure context
   * (HTTPS, or http://localhost / 127.0.0.1). Plain http://192.168.x.x is blocked by the browser — not fixable in JS.
   */
  function liveCameraBlockedReason() {
    if (typeof window.isSecureContext === 'boolean' && window.isSecureContext) {
      return null;
    }
    const h = (location.hostname || '').toLowerCase();
    if (h === 'localhost' || h === '127.0.0.1' || h === '[::1]') {
      return null;
    }
    return 'insecure-http';
  }

  function syncScanPanelLiveUi() {
    const blocked = liveCameraBlockedReason() !== null;
    const banner = $('scanHttpBanner');
    const liveRow = $('scanLiveCameraRow');
    const livePrev = $('scanLivePreviewSection');
    const stopBtn = $('btnStopScan');
    if (banner) banner.classList.toggle('hidden', !blocked);
    if (liveRow) liveRow.classList.toggle('hidden', blocked);
    if (livePrev) livePrev.classList.toggle('hidden', blocked);
    if (stopBtn) stopBtn.classList.toggle('hidden', blocked);
  }

  async function fetchCase(tracking) {
    const r = await fetch(api + '?tracking=' + encodeURIComponent(tracking));
    return r.json();
  }

  function getModalTracking() {
    const el = $('dispTracking');
    if (!el) return '';
    const raw = typeof el.value === 'string' ? el.value : (el.textContent || '');
    return normalizeTracking(raw);
  }

  function setModalTracking(tracking) {
    const el = $('dispTracking');
    if (!el) return;
    el.value = tracking;
  }

  function setTrackingEditWarn(msg) {
    const el = $('trackingEditWarn');
    if (!el) return;
    if (!msg) {
      el.classList.add('hidden');
      el.textContent = '';
      return;
    }
    el.textContent = msg;
    el.classList.remove('hidden');
  }

  function applyDetectedCourierFromApi(det) {
    detectedCourierHint = ['spx', 'jnt', 'flash'].includes(det) ? det : null;
    updateDetectedCourierHint();
    if ($('shop').value) syncCourierForShop();
  }

  let trackingLookupTm = null;
  async function onModalTrackingEdited() {
    const tr = getModalTracking();
    currentTracking = tr;
    setTrackingEditWarn('');
    if (!tr) {
      detectedCourierHint = null;
      updateDetectedCourierHint();
      deliveryMatch = null;
      applyDamagedCompareUi();
      syncFormValidity();
      return;
    }
    if (trackingLookupTm) clearTimeout(trackingLookupTm);
    trackingLookupTm = setTimeout(async () => {
      try {
        const data = await fetchCase(tr);
        if (!data.ok) return;
        if (data.exists) {
          setTrackingEditWarn('This tracking is already in the database. Saving will show the duplicate screen, or close and use Add more products.');
          detectedCourierHint = null;
          updateDetectedCourierHint();
        } else {
          applyDetectedCourierFromApi(data.detected_courier || '');
        }
        void refreshDeliveryMatch();
        syncFormValidity();
      } catch (e) {}
    }, 450);
  }

  function syncCourierForShop() {
    const shopSel = $('shop');
    const courierSel = $('courierFinal');
    const shop = shopSel.value;
    const prev = courierSel.value;
    courierSel.innerHTML = '';
    if (!shop) {
      courierSel.disabled = true;
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'Select shop first';
      courierSel.appendChild(opt);
      courierSel.value = '';
      syncFormValidity();
      return;
    }
    courierSel.disabled = false;
    const allowed = SHOP_COURIERS[shop] || [];
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = '— Select courier —';
    courierSel.appendChild(ph);
    allowed.forEach((c) => {
      const opt = document.createElement('option');
      opt.value = c;
      opt.textContent = COURIER_LABELS[c] || c;
      courierSel.appendChild(opt);
    });
    if (detectedCourierHint && allowed.includes(detectedCourierHint)) {
      courierSel.value = detectedCourierHint;
    } else if (prev && allowed.includes(prev)) {
      courierSel.value = prev;
    } else {
      courierSel.value = '';
    }
    syncFormValidity();
  }

  function updateDetectedCourierHint() {
    const el = $('detectedCourierHint');
    if (!el) return;
    if (!detectedCourierHint) {
      el.classList.add('hidden');
      el.textContent = '';
      return;
    }
    const shop = $('shop').value;
    const allowed = shop ? (SHOP_COURIERS[shop] || []) : [];
    const label = COURIER_LABELS[detectedCourierHint] || detectedCourierHint;
    let msg = 'Detected from tracking: ' + label + '.';
    if (shop && !allowed.includes(detectedCourierHint)) {
      msg += ' Not available for this shop — pick an allowed courier.';
    } else if (!shop) {
      msg += ' Select shop to choose courier.';
    }
    el.textContent = msg;
    el.classList.remove('hidden');
  }

  function getFormValidation() {
    const missing = [];
    if (!getModalTracking()) missing.push('Tracking number');
    if (!$('shop').value) missing.push('Shop');
    const shopVal = $('shop').value;
    const courierVal = $('courierFinal').value;
    if (!shopVal || !courierVal) missing.push('Courier');
    else if (!SHOP_COURIERS[shopVal] || !SHOP_COURIERS[shopVal].includes(courierVal)) {
      missing.push('Courier allowed for shop');
    }
    if (!$('dateDelivered').value) missing.push('Date delivered');
    if (!$('dateOpened').value) missing.push('Date opened / recorded');
    if (!pendingLines.length) missing.push('At least one product');
    if ($('damagedCheck').checked) {
      if (!$('damagedReason').value) missing.push('Damage reason');
      if (!deliveryMatch || !deliveryMatch.match) missing.push('Pre-delivery photo (Deliveries)');
    }
    return { ok: missing.length === 0, missing };
  }

  function syncFormValidity() {
    const v = getFormValidation();
    const btn = $('btnSave');
    if (btn) btn.disabled = !v.ok;
    const banner = $('formValidationBanner');
    if (banner) {
      if (v.ok) {
        banner.classList.add('hidden');
        banner.textContent = '';
      } else {
        banner.classList.remove('hidden');
        banner.textContent = 'Complete required fields: ' + v.missing.join(', ') + '.';
      }
    }
    buildConfirm();
  }

  function setShopHandlers() {
    const shop = $('shop');
    const vipRow = $('vipRow');
    const sync = () => {
      if (shop.value === 'vip') vipRow.classList.remove('hidden');
      else vipRow.classList.add('hidden');
      syncCourierForShop();
      updateDetectedCourierHint();
    };
    shop.addEventListener('change', sync);
    $('courierFinal').addEventListener('change', syncFormValidity);
    sync();
  }

  const PRODUCT_LABELS = <?= json_encode(RTS_PRODUCT_LABELS, JSON_UNESCAPED_UNICODE) ?>;
  const HONEY_SIZE_LABELS = <?= json_encode(RTS_HONEY_SIZE_LABELS, JSON_UNESCAPED_UNICODE) ?>;
  const HONEY_VARIANT_LABELS = <?= json_encode(RTS_HONEY_VARIANT_LABELS, JSON_UNESCAPED_UNICODE) ?>;
  const COMBO_PRODUCT_LABELS = <?= json_encode(RTS_COMBO_PRODUCT_LABELS, JSON_UNESCAPED_UNICODE) ?>;
  const TYPES_HONEY_MAIN = <?= json_encode(RTS_TYPES_WITH_HONEY_MAIN) ?>;
  const TYPES_COMBO = <?= json_encode(RTS_TYPES_WITH_COMBO) ?>;
  const TYPES_COMBO_HONEY = <?= json_encode(RTS_TYPES_WITH_COMBO_HONEY) ?>;
  const TYPES_COMBO_OTHER = <?= json_encode(RTS_TYPES_WITH_COMBO_OTHER) ?>;

  function typeNeedsHoney(t) { return TYPES_HONEY_MAIN.includes(t); }
  function typeHasCombo(t) { return TYPES_COMBO.includes(t); }
  function typeComboIsHoney(t) { return TYPES_COMBO_HONEY.includes(t); }
  function typeComboIsOther(t) { return TYPES_COMBO_OTHER.includes(t); }

  function variantListId(prefix, field) {
    if (field === 'comboHoney') {
      return prefix + (prefix ? 'comboHoneyVariantList' : 'comboHoneyVariantList');
    }
    return prefix + 'honeyVariantList';
  }

  function createVariantRow(prefix, field, variant, qty) {
    const row = document.createElement('div');
    row.className = 'variant-row';
    let opts = '';
    Object.keys(HONEY_VARIANT_LABELS).forEach((k) => {
      opts += '<option value="' + k + '"' + (k === variant ? ' selected' : '') + '>' + HONEY_VARIANT_LABELS[k] + '</option>';
    });
    row.innerHTML =
      '<div><label>Variant</label><select class="vr-variant">' + opts + '</select></div>' +
      '<div><label>Qty</label><input type="number" class="vr-qty" min="0" step="1" value="' + (qty || 0) + '"></div>' +
      '<button type="button" class="btn btn-ghost btn-remove-variant">Remove</button>';
    row.querySelector('.btn-remove-variant').addEventListener('click', () => {
      const list = $(variantListId(prefix, field));
      if (list && list.querySelectorAll('.variant-row').length > 1) row.remove();
    });
    return row;
  }

  function initVariantList(prefix, field) {
    const list = $(variantListId(prefix, field));
    if (!list) return;
    list.innerHTML = '';
    list.appendChild(createVariantRow(prefix, field, 'light', 1));
  }

  function readVariantRows(prefix, field) {
    const list = $(variantListId(prefix, field));
    if (!list) return [];
    const out = [];
    list.querySelectorAll('.variant-row').forEach((row) => {
      const v = row.querySelector('.vr-variant').value;
      const q = parseInt(row.querySelector('.vr-qty').value, 10) || 0;
      if (q > 0) out.push({ variant: v, qty: q });
    });
    return out;
  }

  function formatVariantBreakdown(variants, sizeLabel) {
    if (!variants || !variants.length) return '';
    const parts = variants.map((v) => v.qty + ' ' + (HONEY_VARIANT_LABELS[v.variant] || v.variant));
    return (sizeLabel ? sizeLabel + ': ' : '') + parts.join(', ');
  }

  function setupLineForm(prefix) {
    const sel = $(prefix + 'lineProductType');
    const wrapHoney = $(prefix + 'wrapHoneyMain');
    const wrapComboOther = $(prefix + 'wrapComboOther');
    const wrapComboHoney = $(prefix + 'wrapComboHoney');
    const wrapLineQty = $(prefix + 'wrapLineQty');
    if (!sel) return;
    const sync = () => {
      const pt = sel.value;
      if (wrapHoney) wrapHoney.classList.toggle('hidden', !typeNeedsHoney(pt));
      if (wrapComboOther) wrapComboOther.classList.toggle('hidden', !typeComboIsOther(pt));
      if (wrapComboHoney) wrapComboHoney.classList.toggle('hidden', !typeComboIsHoney(pt));
      if (wrapLineQty) wrapLineQty.classList.toggle('hidden', typeNeedsHoney(pt));
    };
    sel.addEventListener('change', sync);
    sync();
  }

  function readSimpleLineForm(prefix) {
    const pt = $(prefix + 'lineProductType').value;
    const qty = parseFloat($(prefix + 'lineQty').value) || 0;
    if (qty < 1) return null;
    return { product_type: pt, quantity: qty, honey_size: null, honey_variant: null, combo_product: null, combo_quantity: null, combo_honey_size: null, combo_honey_variant: null, is_bonus: false };
  }

  function buildLinesFromForm(prefix) {
    const pt = $(prefix + 'lineProductType').value;
    if (typeNeedsHoney(pt)) {
      const variants = readVariantRows(prefix, 'honey');
      if (!variants.length) { toast('Add at least one variant with quantity', true); return null; }
      const size = $(prefix + 'honeySize').value;
      const line = {
        product_type: pt,
        honey_size: size,
        variants: variants,
        quantity: variants.reduce((s, v) => s + v.qty, 0),
        combo_product: null,
        combo_quantity: null,
        is_bonus: false,
        _grouped: true
      };
      if (typeComboIsOther(pt)) {
        line.combo_product = $(prefix + 'comboProduct').value;
        line.combo_quantity = parseFloat($(prefix + 'comboQty').value) || 1;
      }
      return [line];
    }
    if (typeComboIsHoney(pt)) {
      const mainQty = parseFloat($(prefix + 'lineQty').value) || 0;
      if (mainQty < 1) { toast('Enter product quantity', true); return null; }
      const variants = readVariantRows(prefix, 'comboHoney');
      if (!variants.length) { toast('Add at least one honey variant with quantity', true); return null; }
      return [{
        product_type: pt,
        quantity: mainQty,
        combo_honey_size: $(prefix + 'comboHoneySize').value,
        combo_variants: variants,
        is_bonus: false,
        _grouped: true
      }];
    }
    const simple = readSimpleLineForm(prefix);
    return simple ? [simple] : null;
  }

  function flattenLinesForApi(lines) {
    const out = [];
    const pendingFirstFlat = [];
    lines.forEach((l, pi) => {
      pendingFirstFlat[pi] = out.length;
      if (l._grouped && l.variants && l.variants.length) {
        l.variants.forEach((v, vi) => {
          out.push({
            product_type: l.product_type,
            honey_size: l.honey_size,
            honey_variant: v.variant,
            quantity: v.qty,
            combo_product: vi === 0 && l.combo_product ? l.combo_product : null,
            combo_quantity: vi === 0 && l.combo_product ? l.combo_quantity : null,
            combo_honey_size: null,
            combo_honey_variant: null,
            is_bonus: l.is_bonus ? 1 : 0,
            _bonus_parent_pending: l.is_bonus ? l._bonus_parent_idx : null
          });
        });
      } else if (l._grouped && l.combo_variants && l.combo_variants.length) {
        l.combo_variants.forEach((v) => {
          out.push({
            product_type: l.product_type,
            quantity: v.qty,
            honey_size: null,
            honey_variant: null,
            combo_product: null,
            combo_quantity: null,
            combo_honey_size: l.combo_honey_size,
            combo_honey_variant: v.variant,
            is_bonus: l.is_bonus ? 1 : 0,
            _bonus_parent_pending: l.is_bonus ? l._bonus_parent_idx : null
          });
        });
      } else {
        out.push(Object.assign({}, l, {
          is_bonus: l.is_bonus ? 1 : 0,
          _bonus_parent_pending: l.is_bonus ? l._bonus_parent_idx : null
        }));
      }
    });
    out.forEach((row) => {
      if (row._bonus_parent_pending != null && row._bonus_parent_pending !== '') {
        row._bonus_parent_idx = pendingFirstFlat[row._bonus_parent_pending];
      }
      delete row._bonus_parent_pending;
    });
    return out;
  }

  function resetLineForm(prefix) {
    $(prefix + 'lineProductType').value = 'honey';
    $(prefix + 'lineQty').value = '1';
    if ($(prefix + 'honeySize')) $(prefix + 'honeySize').value = '250ml';
    if ($(prefix + 'comboProduct')) $(prefix + 'comboProduct').selectedIndex = 0;
    if ($(prefix + 'comboQty')) $(prefix + 'comboQty').value = '1';
    if ($(prefix + 'comboHoneySize')) $(prefix + 'comboHoneySize').value = '250ml';
    initVariantList(prefix, 'honey');
    initVariantList(prefix, 'comboHoney');
    $(prefix + 'lineProductType').dispatchEvent(new Event('change'));
  }

  function formatLineHuman(l) {
    const pt = l.product_type;
    let text = '';
    if (l.variants && l.variants.length) {
      const sz = HONEY_SIZE_LABELS[l.honey_size] || l.honey_size || '';
      const label = PRODUCT_LABELS[pt] || pt;
      text = label + ' (' + sz + '): ' + formatVariantBreakdown(l.variants, '');
      if (typeComboIsOther(pt) && l.combo_product) {
        text += ' + ' + (l.combo_quantity || 1) + ' x ' + (COMBO_PRODUCT_LABELS[l.combo_product] || l.combo_product);
      }
    } else if (l.combo_variants && l.combo_variants.length) {
      const sz = HONEY_SIZE_LABELS[l.combo_honey_size] || l.combo_honey_size || '';
      text = (l.quantity || 0) + ' x ' + (PRODUCT_LABELS[pt] || pt);
      text += ' + Honey (' + sz + '): ' + formatVariantBreakdown(l.combo_variants, '');
    } else if (typeNeedsHoney(pt)) {
      const sz = HONEY_SIZE_LABELS[l.honey_size] || l.honey_size || '';
      const vr = HONEY_VARIANT_LABELS[l.honey_variant] || l.honey_variant || '';
      text = l.quantity + ' x Honey ' + vr + ' (' + sz + ')';
    } else {
      text = l.quantity + ' x ' + (PRODUCT_LABELS[pt] || pt);
    }
    if (typeComboIsOther(pt) && l.combo_product && !l.variants) {
      text += ' + ' + (l.combo_quantity || 1) + ' x ' + (COMBO_PRODUCT_LABELS[l.combo_product] || l.combo_product);
    }
    if (typeComboIsHoney(pt) && l.combo_honey_size && !l.combo_variants) {
      const csz = HONEY_SIZE_LABELS[l.combo_honey_size] || l.combo_honey_size;
      const cvr = HONEY_VARIANT_LABELS[l.combo_honey_variant] || l.combo_honey_variant;
      text += ' + ' + (l.combo_quantity || 1) + ' x Honey ' + cvr + ' (' + csz + ')';
    }
    if (l.is_bonus) text = '[Bonus] ' + text;
    return text;
  }

  function renderPending(targetId, lines, onRemove) {
    const el = $(targetId);
    el.innerHTML = '';
    if (!lines.length) {
      el.innerHTML = '<p style="margin:0;color:var(--muted);font-size:0.9rem;">No lines yet.</p>';
      return;
    }
    lines.forEach((l, idx) => {
      const row = document.createElement('div');
      row.className = 'line-chip' + (l.is_bonus ? ' is-bonus' : '');
      row.innerHTML = '<span>' + formatLineHuman(l) + '</span>';
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'btn btn-ghost btn-danger';
      rm.textContent = 'Remove';
      rm.addEventListener('click', () => onRemove(idx));
      row.appendChild(rm);
      el.appendChild(row);
    });
  }

  function resetDamagedUiPartial() {
    $('modalBackdrop').classList.remove('damaged-delivery-tone');
    $('damagedCheck').checked = false;
    $('damagedReason').value = '';
    $('damagedReasonRow').classList.add('hidden');
    $('deliveryCompareBlock').classList.add('hidden');
    $('damagedGateMsg').classList.add('hidden');
    const dImg = $('deliveryCompareImg');
    dImg.classList.add('hidden');
    dImg.removeAttribute('src');
    $('deliveryCompareDl').classList.add('hidden');
    $('deliveryCompareOpen').classList.add('hidden');
    const rWrap = $('rtsCompareAttachments');
    if (rWrap) rWrap.innerHTML = '';
    deliveryMatch = null;
  }

  function renderDocAttachmentsPanel() {
    const list = $('docAttachmentsList');
    if (!list) return;
    list.innerHTML = '';
    documentationAttachments.forEach((a) => {
      const fname = a.file_name;
      const tile = document.createElement('div');
      tile.className = 'doc-attach-tile';
      if (a.kind === 'video') {
        const v = document.createElement('video');
        v.src = a.file_url;
        v.controls = true;
        v.playsInline = true;
        v.preload = 'metadata';
        tile.appendChild(v);
      } else {
        const im = document.createElement('img');
        im.src = a.file_url;
        im.alt = 'RTS proof';
        tile.appendChild(im);
      }
      const row = document.createElement('div');
      row.className = 'toolbar';
      row.style.marginTop = '0.35rem';
      const dl = document.createElement('a');
      dl.className = 'btn btn-ghost';
      dl.href = a.file_url;
      dl.download = fname;
      dl.textContent = 'Download';
      row.appendChild(dl);
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'btn btn-ghost btn-danger';
      rm.textContent = 'Remove';
      rm.addEventListener('click', () => {
        documentationAttachments = documentationAttachments.filter((x) => x.file_name !== fname);
        renderDocAttachmentsPanel();
        applyDamagedCompareUi();
        syncFormValidity();
      });
      row.appendChild(rm);
      tile.appendChild(row);
      list.appendChild(tile);
    });
    const lab = $('docFileName');
    if (lab) {
      lab.textContent = documentationAttachments.length ? documentationAttachments.length + ' file(s)' : '';
    }
  }

  function renderRtsCompareStrip() {
    const wrap = $('rtsCompareAttachments');
    const ph = $('rtsDocComparePlaceholder');
    if (!wrap || !ph) return;
    wrap.innerHTML = '';
    if (!$('damagedCheck').checked) return;
    if (!documentationAttachments.length) {
      ph.classList.remove('hidden');
      return;
    }
    ph.classList.add('hidden');
    documentationAttachments.forEach((a) => {
      const tile = document.createElement('div');
      tile.className = 'doc-attach-tile';
      tile.style.maxWidth = '100%';
      if (a.kind === 'video') {
        const v = document.createElement('video');
        v.src = a.file_url;
        v.controls = true;
        v.playsInline = true;
        v.preload = 'metadata';
        tile.appendChild(v);
      } else {
        const im = document.createElement('img');
        im.src = a.file_url;
        im.alt = 'RTS proof';
        tile.appendChild(im);
      }
      const row = document.createElement('div');
      row.className = 'toolbar';
      row.style.marginTop = '0.35rem';
      const dl = document.createElement('a');
      dl.className = 'btn btn-ghost';
      dl.href = a.file_url;
      dl.download = a.file_name;
      dl.textContent = 'Download';
      row.appendChild(dl);
      tile.appendChild(row);
      wrap.appendChild(tile);
    });
  }

  function refreshRtsCompareHalf() {
    renderRtsCompareStrip();
  }

  function updateDeliveryCompareHalf(showMatchUi) {
    const img = $('deliveryCompareImg');
    const ph = $('deliveryComparePlaceholder');
    const dl = $('deliveryCompareDl');
    const op = $('deliveryCompareOpen');
    if (showMatchUi && deliveryMatch && deliveryMatch.match && deliveryMatch.file_url) {
      img.src = deliveryMatch.file_url;
      img.classList.remove('hidden');
      ph.classList.add('hidden');
      dl.href = deliveryMatch.file_url;
      dl.download = deliveryMatch.photo_filename || 'before-delivery.jpg';
      dl.classList.remove('hidden');
      op.href = deliveryMatch.file_url;
      op.classList.remove('hidden');
    } else {
      img.classList.add('hidden');
      img.removeAttribute('src');
      ph.classList.remove('hidden');
      dl.classList.add('hidden');
      op.classList.add('hidden');
    }
  }

  function applyDamagedCompareUi() {
    const chk = $('damagedCheck').checked;
    $('damagedReasonRow').classList.toggle('hidden', !chk);
    if (!chk) {
      $('deliveryCompareBlock').classList.add('hidden');
      $('damagedGateMsg').classList.add('hidden');
      $('modalBackdrop').classList.remove('damaged-delivery-tone');
      return;
    }
    $('deliveryCompareBlock').classList.remove('hidden');
    const ok = deliveryMatch && deliveryMatch.match === true;
    if (ok) {
      $('damagedGateMsg').classList.add('hidden');
      $('modalBackdrop').classList.add('damaged-delivery-tone');
      updateDeliveryCompareHalf(true);
    } else {
      $('damagedGateMsg').classList.remove('hidden');
      $('modalBackdrop').classList.remove('damaged-delivery-tone');
      updateDeliveryCompareHalf(false);
    }
    refreshRtsCompareHalf();
  }

  async function refreshDeliveryMatch() {
    const tr = getModalTracking() || currentTracking || '';
    deliveryMatch = null;
    if (!tr) {
      applyDamagedCompareUi();
      return;
    }
    try {
      const r = await fetch(deliveryMatchApi + '?tracking=' + encodeURIComponent(tr));
      const j = await r.json();
      if (!j.ok) {
        deliveryMatch = { match: false };
      } else {
        deliveryMatch = j;
      }
    } catch (e) {
      deliveryMatch = { match: false };
    }
    applyDamagedCompareUi();
    syncFormValidity();
  }

  function showModalNew() {
    $('modalBackdrop').classList.remove('hidden');
    $('modalTitle').textContent = 'New RTS';
    $('modalStepLabel').textContent = 'Fill out details and save in one screen';
    setTrackingEditWarn('');
    pendingLines = [];
    documentationAttachments = [];
    $('docFileInput').value = '';
    $('docCameraInput').value = '';
    renderDocAttachmentsPanel();
    resetDamagedUiPartial();
    updateDetectedCourierHint();
    syncCourierForShop();
    renderPendingLines();
    syncFormValidity();
    void refreshDeliveryMatch();
  }

  function hideModalNew() {
    $('modalBackdrop').classList.add('hidden');
    resetDamagedUiPartial();
  }

  function openScanPanel() {
    $('scanPanel').classList.remove('hidden');
    syncScanPanelLiveUi();
    if (liveCameraBlockedReason()) {
      void stopScannerAsync();
      return;
    }
    startScanner();
  }

  function closeScanPanel() {
    stopScanner();
    $('scanPanel').classList.add('hidden');
    $('manualTracking').value = '';
    clearQrFileInputs();
    $('docFileInput').value = '';
    $('docCameraInput').value = '';
  }

  async function uploadMediaFile(file) {
    if (!file) return;
    if (documentationAttachments.length >= RTS_ATTACHMENTS_MAX) {
      toast('Maximum ' + RTS_ATTACHMENTS_MAX + ' proof files per RTS.', true);
      return;
    }
    const fd = new FormData();
    fd.append('file', file);
    const r = await fetch(uploadApi, { method: 'POST', body: fd });
    const j = await r.json();
    if (!j.ok) {
      toast(j.error || 'Upload failed', true);
      return;
    }
    const kind = j.media_kind === 'video' ? 'video' : 'image';
    documentationAttachments.push({
      kind,
      file_name: j.file_name || '',
      file_url: j.file_url || ''
    });
    renderDocAttachmentsPanel();
    applyDamagedCompareUi();
    syncFormValidity();
  }

  async function stopScannerAsync() {
    if (!html5Qr) return;
    const inst = html5Qr;
    html5Qr = null;
    try {
      await inst.stop();
    } catch (e) {
      try {
        await inst.clear();
      } catch (e2) {}
    }
  }

  function stopScanner() {
    void stopScannerAsync();
  }

  const CAM_LS = 'rts_cam_device_id';

  async function refreshCameraDropdown() {
    const sel = $('camSelect');
    if (liveCameraBlockedReason()) {
      sel.innerHTML = '';
      return false;
    }
    if (typeof Html5Qrcode === 'undefined') {
      sel.innerHTML = '';
      return false;
    }
    let cams;
    try {
      cams = await Html5Qrcode.getCameras();
    } catch (e) {
      sel.innerHTML = '';
      toast('Could not access cameras (permission denied?). You can still use Take photo of QR, Gallery, or manual.', true);
      return false;
    }
    const previous = sel.value;
    sel.innerHTML = '';
    if (!cams || !cams.length) {
      toast('No camera found. Use photo upload or manual entry.', true);
      return false;
    }
    cams.forEach((cam, i) => {
      const opt = document.createElement('option');
      opt.value = cam.id;
      opt.textContent = cam.label ? cam.label : ('Camera ' + (i + 1));
      sel.appendChild(opt);
    });
    const saved = localStorage.getItem(CAM_LS);
    if (saved && [...sel.options].some((o) => o.value === saved)) {
      sel.value = saved;
    } else if (previous && [...sel.options].some((o) => o.value === previous)) {
      sel.value = previous;
    } else if (sel.options.length > 0) {
      sel.selectedIndex = 0;
    }
    return true;
  }

  async function startScanner() {
    await stopScannerAsync();
    if (liveCameraBlockedReason()) {
      return;
    }
    if (typeof Html5Qrcode === 'undefined') {
      toast('Scanner library failed to load. Use manual entry.', true);
      return;
    }
    const hasCameras = await refreshCameraDropdown();
    if (!hasCameras) return;
    const sel = $('camSelect');
    const deviceId = sel.value;
    if (!deviceId) return;
    html5Qr = new Html5Qrcode('reader');
    try {
      await html5Qr.start(
        { deviceId: { exact: deviceId } },
        {
          fps: 10,
          qrbox: function (vw, vh) {
            var s = Math.min(vw, vh);
            var q = Math.min(280, Math.floor(s * 0.72));
            return { width: q, height: q };
          }
        },
        function (decoded) { onGotTracking(normalizeTracking(decoded)); },
        function () {}
      );
    } catch (e) {
      html5Qr = null;
      toast('Camera failed to start. Try Take photo of QR, Gallery, or manual entry.', true);
    }
  }

  async function hasCameraHardware() {
    if (liveCameraBlockedReason()) return false;
    if (typeof Html5Qrcode === 'undefined') return false;
    try {
      var cams = await Html5Qrcode.getCameras();
      return !!(cams && cams.length);
    } catch (e) {
      return false;
    }
  }

  /**
   * Server-side decode (PHP + chillerlan/php-qrcode): works on http://LAN without getUserMedia.
   */
  async function tryServerQrDecode(blob, mime) {
    try {
      const fd = new FormData();
      const file = blob instanceof File
        ? blob
        : new File([blob], 'qr-paste.png', { type: mime || 'image/png' });
      fd.append('image', file);
      const r = await fetch(qrDecodeApi, { method: 'POST', body: fd });
      if (!r.ok) return null;
      const j = await r.json();
      if (j && j.ok && j.text) return String(j.text);
    } catch (e) {
      return null;
    }
    return null;
  }

  /**
   * Decode QR from an uploaded/pasted image (no live camera).
   * Tries PHP server decode first, then html5-qrcode in the browser.
   */
  async function scanQrFromImageBlob(blob, sourceLabel) {
    if (!blob) {
      toast('No image provided.', true);
      return;
    }
    var mime = (blob.type || '').trim().toLowerCase();
    if (blob instanceof File && /\.(heic|heif)$/i.test(blob.name || '')) {
      toast('HEIC photos are often unsupported in browsers. Use JPG/PNG upload or paste a PNG screenshot.', true);
      clearQrFileInputs();
      return;
    }
    if (!mime || mime.indexOf('image/') !== 0) {
      mime = 'image/png';
    }
    await stopScannerAsync();
    await new Promise(function (r) { setTimeout(r, 120); });

    const serverText = await tryServerQrDecode(blob, mime);
    if (serverText) {
      clearQrFileInputs();
      onGotTracking(normalizeTracking(serverText));
      return;
    }

    if (typeof Html5Qrcode === 'undefined') {
      toast('Could not read QR (server decode failed and scanner script is missing). Check PHP GD and composer install.', true);
      clearQrFileInputs();
      return;
    }

    var file = blob instanceof File
      ? blob
      : new File([blob], 'qr-image.png', { type: mime });

    var h = null;
    try {
      h = new Html5Qrcode('qrFileReader');
      var text = await h.scanFile(file, false);
      await h.clear();
      clearQrFileInputs();
      onGotTracking(normalizeTracking(text));
    } catch (err) {
      if (h) {
        try { await h.clear(); } catch (e2) {}
      }
      clearQrFileInputs();
      var hint = sourceLabel === 'paste'
        ? 'Try cropping so only the QR is visible, or use Take photo of QR / Gallery.'
        : 'Use a sharper JPG/PNG with the QR in frame (not HEIC). Or enter tracking manually.';
      toast('Could not read QR from that image. ' + hint, true);
      if (!$('scanPanel').classList.contains('hidden')) {
        if (await hasCameraHardware()) await startScanner();
      }
    }
  }

  async function onGotTracking(tracking) {
    if (!tracking) return;
    currentTracking = tracking;
    await stopScannerAsync();
    $('scanPanel').classList.add('hidden');
    const data = await fetchCase(tracking);
    if (!data.ok) {
      toast(data.error || 'Lookup failed', true);
      openScanPanel();
      return;
    }
    if (data.exists) {
      showDuplicate(tracking, data);
      return;
    }
    setModalTracking(tracking);
    setTrackingEditWarn('');
    applyDetectedCourierFromApi(data.detected_courier || '');
    $('shop').value = '';
    $('vipSub').value = '';
    $('dateDelivered').value = '';
    $('dateOpened').value = todayISO();
    pendingLines = [];
    renderPendingLines();
    showModalNew();
  }

  function renderPendingLines() {
    renderPending('pendingLines', pendingLines, (idx) => {
      pendingLines.splice(idx, 1);
      renderPendingLines();
    });
    syncFormValidity();
  }

  function showDuplicate(tracking, data) {
    $('dupTracking').textContent = tracking;
    const c = data.case;
    const lines = data.lines || [];
    const courierLabels = { spx: 'SPX', jnt: 'J&T', flash: 'Flash', unknown: 'Unknown' };
    const shopLabels = { shopee: 'Shopee', lazada: 'Lazada', tiktok: 'TikTok', vip: 'VIP' };
    let html = '<dl class="summary" style="margin:0;">';
    html += '<dt>Courier (final)</dt><dd>' + (courierLabels[c.courier_final] || c.courier_final) + '</dd>';
    html += '<dt>Detected</dt><dd>' + (courierLabels[c.courier_detected] || c.courier_detected || '—') + '</dd>';
    html += '<dt>Shop</dt><dd>' + (shopLabels[c.shop] || c.shop);
    if (c.shop === 'vip' && c.vip_subsource) {
      html += ' — ' + (c.vip_subsource === 'website' ? 'Website' : 'Facebook');
    }
    html += '</dd>';
    html += '<dt>Date delivered</dt><dd>' + (c.date_delivered || '—') + '</dd>';
    html += '<dt>Date opened / recorded</dt><dd>' + (c.date_opened || '—') + '</dd>';
    html += '<dt>Damaged (record)</dt><dd>' + String(c.damaged_tag || 'no').replace(/</g, '') + '</dd>';
    if (c.damaged_reason && c.delivery_match_item_id) {
      html += '<dt>Linked Deliveries item</dt><dd>#' + c.delivery_match_item_id + '</dd>';
    }
    const atts = Array.isArray(c.documentation_attachments) ? c.documentation_attachments : [];
    if (atts.length) {
      html += '<dt>RTS proof files</dt><dd><ul style="margin:0.25rem 0 0 1rem;padding:0;">';
      atts.forEach((row) => {
        html += '<li class="mono">' + String(row.kind || '').replace(/</g, '') + ': ' + String(row.file || '').replace(/</g, '') + '</li>';
      });
      html += '</ul></dd>';
    } else if (c.documentation_photo) {
      html += '<dt>RTS proof files</dt><dd class="mono">' + String(c.documentation_photo).replace(/</g, '') + '</dd>';
    }
    html += '<dt>Line items</dt><dd><ul style="margin:0.25rem 0 0 1rem;padding:0;">';
    if (!lines.length) html += '<li>None</li>';
    else lines.forEach((l) => {
      html += '<li>' + formatLineHuman(l) + '</li>';
    });
    html += '</ul></dd></dl>';
    $('dupDetails').innerHTML = html;
    $('dupBackdrop').classList.remove('hidden');
  }

  function hideDup() {
    $('dupBackdrop').classList.add('hidden');
  }

  function showAppendModal(tracking) {
    currentTracking = tracking;
    appendLines = [];
    $('appendTracking').textContent = tracking;
    renderAppendPending();
    $('appendBackdrop').classList.remove('hidden');
  }

  function hideAppend() {
    $('appendBackdrop').classList.add('hidden');
  }

  function renderAppendPending() {
    renderPending('appendPendingLines', appendLines, (idx) => {
      appendLines.splice(idx, 1);
      renderAppendPending();
    });
    $('a_btnSave').disabled = appendLines.length === 0;
  }

  $('btnStart').addEventListener('click', () => openScanPanel());
  $('camSelect').addEventListener('change', async () => {
    localStorage.setItem(CAM_LS, $('camSelect').value);
    if (liveCameraBlockedReason()) return;
    if (!$('scanPanel').classList.contains('hidden') && html5Qr) {
      await startScanner();
    }
  });
  $('btnRefreshCams').addEventListener('click', async () => {
    if (liveCameraBlockedReason()) {
      toast('Live preview needs HTTPS (or localhost). Use Take photo of QR or type the tracking number.', true);
      return;
    }
    const open = !$('scanPanel').classList.contains('hidden');
    if (open) await startScanner();
    else await refreshCameraDropdown();
  });
  $('btnCancelScan').addEventListener('click', () => closeScanPanel());
  $('btnStopScan').addEventListener('click', () => stopScanner());
  $('btnUseManual').addEventListener('click', () => {
    const t = normalizeTracking($('manualTracking').value);
    if (!t) { toast('Enter a tracking number', true); return; }
    onGotTracking(t);
  });
  $('qrFileInput').addEventListener('change', async () => {
    const inp = $('qrFileInput');
    const f = inp.files && inp.files[0];
    if (!f) return;
    await scanQrFromImageBlob(f, 'upload');
  });
  $('qrCameraInput').addEventListener('change', async () => {
    const inp = $('qrCameraInput');
    const f = inp.files && inp.files[0];
    if (!f) return;
    await scanQrFromImageBlob(f, 'upload');
  });
  $('btnQrFromPaste').addEventListener('click', async () => {
    if (!navigator.clipboard || typeof navigator.clipboard.read !== 'function') {
      toast('Clipboard images are not supported in this browser. Use Gallery or Take photo of QR.', true);
      return;
    }
    try {
      const items = await navigator.clipboard.read();
      for (let i = 0; i < items.length; i++) {
        const ci = items[i];
        for (let j = 0; j < ci.types.length; j++) {
          const type = ci.types[j];
          if (type.indexOf('image/') === 0) {
            const blob = await ci.getType(type);
            await scanQrFromImageBlob(blob, 'paste');
            return;
          }
        }
      }
      toast('No image in clipboard. Take a screenshot (e.g. Snipping Tool), copy it, then click again.', true);
    } catch (e) {
      toast('Could not read clipboard. Allow permission when asked, or use Upload photo.', true);
    }
  });

  $('dupClose').addEventListener('click', () => { hideDup(); closeScanPanel(); });
  $('dupAppend').addEventListener('click', () => {
    const t = $('dupTracking').textContent;
    hideDup();
    showAppendModal(t);
  });

  $('a_btnCancel').addEventListener('click', () => { hideAppend(); closeScanPanel(); });
  $('a_btnSave').addEventListener('click', async () => {
    const body = {
      action: 'append_lines',
      tracking_number: currentTracking,
      lines: flattenLinesForApi(appendLines)
    };
    const r = await fetch(api, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const j = await r.json();
    if (!j.ok) {
      toast(j.error || 'Save failed', true);
      return;
    }
    toast('Lines added.');
    showSuccess();
    hideAppend();
    closeScanPanel();
  });

  setShopHandlers();
  setupLineForm('');
  setupLineForm('a_');

  function addLineFromForm(prefix, targetLines, renderFn) {
    const built = buildLinesFromForm(prefix);
    if (!built) return;
    built.forEach((line) => targetLines.push(line));
    resetLineForm(prefix);
    renderFn();
  }

  function addBonusFromForm(prefix, targetLines, renderFn) {
    const built = buildLinesFromForm(prefix);
    if (!built) return;
    let parentIdx = -1;
    for (let i = targetLines.length - 1; i >= 0; i--) {
      if (!targetLines[i].is_bonus) { parentIdx = i; break; }
    }
    if (parentIdx < 0) { toast('Add a main product first before adding a bonus.', true); return; }
    built.forEach((line) => {
      line.is_bonus = true;
      line._bonus_parent_idx = parentIdx;
      targetLines.push(line);
    });
    resetLineForm(prefix);
    renderFn();
  }

  function wireVariantButtons(prefix) {
    const addHoney = $(prefix + 'btnAddHoneyVariant');
    const addCombo = $(prefix + 'btnAddComboHoneyVariant');
    if (addHoney) {
      addHoney.addEventListener('click', () => {
        const list = $(variantListId(prefix, 'honey'));
        if (list) list.appendChild(createVariantRow(prefix, 'honey', 'light', 0));
      });
    }
    if (addCombo) {
      addCombo.addEventListener('click', () => {
        const list = $(variantListId(prefix, 'comboHoney'));
        if (list) list.appendChild(createVariantRow(prefix, 'comboHoney', 'light', 0));
      });
    }
  }

  initVariantList('', 'honey');
  initVariantList('', 'comboHoney');
  initVariantList('a_', 'honey');
  initVariantList('a_', 'comboHoney');
  wireVariantButtons('');
  wireVariantButtons('a_');

  $('btnAddLine').addEventListener('click', () => addLineFromForm('', pendingLines, renderPendingLines));
  $('btnAddBonus').addEventListener('click', () => addBonusFromForm('', pendingLines, renderPendingLines));

  function buildConfirm() {
    const courierLabels = { spx: 'SPX', jnt: 'J&T', flash: 'Flash', unknown: 'Unknown' };
    const shopLabels = { shopee: 'Shopee', lazada: 'Lazada', tiktok: 'TikTok', vip: 'VIP' };
    let html = '';
    html += '<dt>Tracking</dt><dd class="mono">' + getModalTracking() + '</dd>';
    const cVal = $('courierFinal').value;
    html += '<dt>Shop</dt><dd>' + (shopLabels[$('shop').value] || '<em>Not selected</em>');
    if ($('shop').value === 'vip' && $('vipSub').value) {
      html += ' — ' + ($('vipSub').value === 'website' ? 'Website' : 'Facebook');
    }
    html += '</dd>';
    html += '<dt>Courier</dt><dd>' + (cVal ? (courierLabels[cVal] || cVal) : '<em>Not selected</em>') + '</dd>';
    html += '<dt>Date delivered</dt><dd>' + ($('dateDelivered').value || '—') + '</dd>';
    html += '<dt>Date opened / recorded</dt><dd>' + ($('dateOpened').value || '—') + '</dd>';
    let docSummary = '—';
    if (documentationAttachments.length) {
      const imgs = documentationAttachments.filter((x) => x.kind === 'image').length;
      const vids = documentationAttachments.filter((x) => x.kind === 'video').length;
      docSummary = documentationAttachments.length + ' file(s) (' + imgs + ' photo(s), ' + vids + ' video(s))';
    }
    html += '<dt>Opening proof</dt><dd>' + docSummary + '</dd>';
    const dmgLabels = { leak: 'Leak', item_replaced: 'Item replaced', packaging_replaced: 'Packaging replaced' };
    const dmgChk = $('damagedCheck').checked;
    const dmgReason = $('damagedReason').value;
    if (dmgChk && dmgReason) {
      html += '<dt>Damaged</dt><dd>Yes — ' + (dmgLabels[dmgReason] || dmgReason) + '</dd>';
      if (deliveryMatch && deliveryMatch.match && deliveryMatch.item_id) {
        html += '<dt>Pre-delivery (Deliveries)</dt><dd>Matched item #' + deliveryMatch.item_id + '</dd>';
      } else {
        html += '<dt>Pre-delivery (Deliveries)</dt><dd><em>No match — cannot save as damaged</em></dd>';
      }
    } else if (dmgChk) {
      html += '<dt>Damaged</dt><dd><em>Select a reason</em></dd>';
    } else {
      html += '<dt>Damaged</dt><dd>No</dd>';
    }
    html += '<dt>Products</dt><dd><ul style="margin:0.25rem 0 0 1rem;">';
    pendingLines.forEach((l) => { html += '<li>' + formatLineHuman(l) + '</li>'; });
    html += '</ul></dd>';
    $('confirmSummary').innerHTML = html;
  }

  $('btnSave').addEventListener('click', async () => {
    const v = getFormValidation();
    if (!v.ok) {
      toast('Complete all required fields: ' + v.missing.join(', '), true);
      return;
    }
    if (!pendingLines.length) {
      toast('Please add at least one product line before saving.', true);
      return;
    }
    if ($('damagedCheck').checked) {
      const dr = $('damagedReason').value;
      if (!dr) {
        toast('Select a damage reason, or uncheck Damaged.', true);
        return;
      }
      if (!deliveryMatch || !deliveryMatch.match) {
        toast('Damaged requires a pre-delivery Deliveries photo with this tracking. Add it in Deliveries or uncheck Damaged.', true);
        return;
      }
    }
    const shopVal = $('shop').value;
    const courierVal = $('courierFinal').value;
    if (!SHOP_COURIERS[shopVal] || !SHOP_COURIERS[shopVal].includes(courierVal)) {
      toast('Select a courier allowed for this shop.', true);
      return;
    }
    const trackingToSave = getModalTracking();
    if (!trackingToSave) {
      toast('Enter a tracking number', true);
      return;
    }
    const payload = {
      action: 'create',
      tracking_number: trackingToSave,
      courier_final: $('courierFinal').value,
      shop: $('shop').value,
      vip_subsource: $('shop').value === 'vip' ? $('vipSub').value : '',
      date_delivered: $('dateDelivered').value || '',
      date_opened: $('dateOpened').value || todayISO(),
      documentation_attachments: documentationAttachments.map((a) => ({ kind: a.kind, file: a.file_name })),
      damaged_reason: $('damagedCheck').checked ? $('damagedReason').value : '',
      lines: flattenLinesForApi(pendingLines)
    };
    const r = await fetch(api, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    const j = await r.json();
    if (!j.ok) {
      if (j.error === 'duplicate_tracking') {
        hideModalNew();
        const data = await fetchCase(payload.tracking_number);
        showDuplicate(payload.tracking_number, data);
        return;
      }
      if (j.error === 'damaged_requires_delivery_photo') {
        toast(j.message || 'A Deliveries pre-delivery photo is required for damaged RTS.', true);
        void refreshDeliveryMatch();
        return;
      }
      toast(j.error || 'Save failed', true);
      return;
    }
    toast('RTS saved.');
    showSuccess();
    hideModalNew();
    closeScanPanel();
  });

  ['shop', 'vipSub', 'dateDelivered', 'dateOpened'].forEach((id) => {
    $(id).addEventListener('change', syncFormValidity);
    $(id).addEventListener('input', syncFormValidity);
  });
  const dispTrackingEl = $('dispTracking');
  if (dispTrackingEl) {
    dispTrackingEl.addEventListener('input', onModalTrackingEdited);
    dispTrackingEl.addEventListener('change', onModalTrackingEdited);
  }
  $('damagedCheck').addEventListener('change', () => {
    applyDamagedCompareUi();
    syncFormValidity();
  });
  $('damagedReason').addEventListener('change', syncFormValidity);
  $('docFileInput').addEventListener('change', async () => {
    const inp = $('docFileInput');
    const files = inp.files ? Array.from(inp.files) : [];
    inp.value = '';
    for (let i = 0; i < files.length; i++) {
      if (documentationAttachments.length >= RTS_ATTACHMENTS_MAX) {
        toast('Maximum ' + RTS_ATTACHMENTS_MAX + ' proof files.', true);
        break;
      }
      await uploadMediaFile(files[i]);
    }
  });
  $('docCameraInput').addEventListener('change', async () => {
    const f = $('docCameraInput').files && $('docCameraInput').files[0];
    if (!f) return;
    await uploadMediaFile(f);
    $('docCameraInput').value = '';
  });

  $('btnModalCancelNew').addEventListener('click', () => {
    hideModalNew();
    closeScanPanel();
  });

  $('a_btnAddLine').addEventListener('click', () => addLineFromForm('a_', appendLines, renderAppendPending));
  $('a_btnAddBonus').addEventListener('click', () => addBonusFromForm('a_', appendLines, renderAppendPending));

  $('modalBackdrop').addEventListener('click', (e) => {
    if (e.target.id === 'modalBackdrop') { /* ignore backdrop close to avoid accidental loss */ }
  });

  // Mobile sidebar menu toggle
  const mBtn = document.getElementById('mobileMenuBtn');
  const mPanel = document.getElementById('mobileMenuPanel');
  if (mBtn && mPanel) {
    const setOpen = (open) => {
      mPanel.classList.toggle('open', !!open);
      mPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
    };
    mBtn.addEventListener('click', () => {
      setOpen(!mPanel.classList.contains('open'));
    });
    mPanel.querySelectorAll('a').forEach((a) => {
      a.addEventListener('click', () => setOpen(false));
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setOpen(false);
    });
  }

  document.querySelectorAll('a[href$=".php"], a[href*=".php?"]').forEach((a) => {
    a.addEventListener('click', (e) => {
      if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      const href = a.getAttribute('href');
      if (!href) return;
      e.preventDefault();
      document.body.classList.add('page-exit');
      setTimeout(() => { window.location.href = href; }, 180);
    });
  });
})();
  </script>
</body>
</html>
