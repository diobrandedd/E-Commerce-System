<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

rts_session_start();
$user = rts_auth_user();

$pageTitle = 'Pyx Tracker — deliveries';
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$todayLabel = (new DateTimeImmutable('today'))->format('M d, Y');
$isAdmin = rts_can_access_rts_admin($user);
$isLoggedIn = $user !== null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <script>
  (function () {
    var NGROK_HOST = 'basket-grouped-viscous.ngrok-free.dev';
    if (location.protocol === 'https:' && location.hostname === NGROK_HOST) return;
    location.replace('https://' + NGROK_HOST + location.pathname + location.search + location.hash);
  })();
  </script>
  <style>
    :root { --bg:#fffdf6; --panel:#ffffff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --ok:#2d6a4f; --danger:#c65a4a; --green:#34a853; }
    * { box-sizing: border-box; }
    body {
      margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); line-height:1.45;
      display:flex; min-height:100vh; opacity:1; transition:opacity .22s ease;
    }
    body.page-enter { animation:pageIn .32s ease both; }
    body.page-exit { opacity:0; }
    @keyframes pageIn { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform: translateY(0); } }
    @keyframes cardIn { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: translateY(0); } }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
    @keyframes popIn { from { opacity:0; transform: translateY(8px) scale(.98); } to { opacity:1; transform: translateY(0) scale(1); } }
    @keyframes check-draw { from { transform: rotate(-45deg) scale(0); } to { transform: rotate(-45deg) scale(1); } }
    @keyframes check-pulse { 0% { transform: scale(0.4); opacity: 0.7; } 100% { transform: scale(1.1); opacity: 0; } }
    .sidebar { width:220px; padding:1.25rem 1rem; border-right:1px solid var(--border); background:#fff5df; display:flex; flex-direction:column; gap:1rem; position:sticky; top:0; align-self:flex-start; height:100vh; overflow-y:auto; }
    .sidebar h1 { margin:0; font-size:1.05rem; font-weight:650; }
    .nav-links a { display:block; padding:0.4rem 0.1rem; color:var(--muted); text-decoration:none; font-size:0.9rem; }
    .nav-links a:hover { color:#4a381d; }
    main { flex:1; max-width:720px; margin:0 auto; padding:1.25rem; overflow-x:hidden; }
    body { overflow-x:hidden; }
    .card {
      background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:1rem 1.1rem; margin-bottom:1rem;
      animation: cardIn .4s ease both;
    }
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:0.55rem 1.1rem; border-radius:10px; border:none; background:var(--accent); color:#061018; font-weight:700; cursor:pointer; font-size:0.95rem; }
    .btn:disabled { opacity:0.55; cursor:not-allowed; }
    .btn-secondary { background:transparent; border:1px solid var(--border); color:var(--text); font-weight:500; }
    .btn-block { width:100%; }
    .muted { color:var(--muted); font-size:0.88rem; }
    #result { font-size:0.88rem; margin-top:0.75rem; }
    #saveErrorBanner {
      display:none; margin-top:0.75rem; padding:0.65rem 0.75rem; border-radius:8px;
      background:#fff6f3; border:1px solid var(--danger); color:var(--danger); font-size:0.88rem; line-height:1.4;
    }
    #saveErrorBanner.visible { display:block; }
    .pill { display:inline-block; padding:0.12rem 0.45rem; border-radius:6px; background:#fff7ea; border:1px solid var(--border); font-size:0.8rem; margin:0.15rem 0.15rem 0 0; }
    .pill.bad { border-color:var(--danger); color:var(--danger); }
    .pill.ok { border-color:var(--ok); color:var(--ok); }
    .hidden { display:none !important; }
    .overlay-backdrop {
      position:fixed; inset:0; background:rgba(75,58,29,0.45); z-index:100;
      display:flex; align-items:center; justify-content:center; padding:1rem;
      animation: fadeIn .2s ease both;
    }
    .overlay-panel {
      background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.1rem 1.2rem;
      max-width:480px; width:100%; max-height:92vh; overflow-y:auto;
      box-shadow:0 16px 48px rgba(101,76,36,0.22);
      animation: popIn .28s ease both;
    }
    .overlay-panel h3 { margin:0 0 0.65rem; font-size:1.05rem; }
    .overlay-header { display:flex; align-items:center; justify-content:space-between; gap:0.5rem; margin-bottom:0.65rem; }
    .overlay-close {
      border:none; background:transparent; font-size:1.35rem; line-height:1; cursor:pointer; color:var(--muted); padding:0.2rem 0.4rem;
    }
    #reader { width:100%; border-radius:10px; overflow:hidden; border:1px solid var(--border); }
    .tracking-display { font-family:ui-monospace,monospace; font-size:0.95rem; word-break:break-all; padding:0.5rem 0.6rem; background:#fffdfa; border:1px solid var(--border); border-radius:8px; }
    .parcel-preview-wrap {
      margin-top:0.75rem; border-radius:10px; overflow:hidden; border:1px solid var(--border);
      background:#f5efe3; max-height:min(50vh, 320px); display:flex; align-items:center; justify-content:center;
    }
    .parcel-preview-wrap img { max-width:100%; max-height:min(50vh, 320px); object-fit:contain; display:block; }
    .capture-fallback {
      margin-top:0.75rem; padding:0.65rem 0.75rem; background:#fff7ea; border:1px solid var(--border); border-radius:8px; font-size:0.88rem;
    }
    .modal-actions { display:flex; flex-wrap:wrap; gap:0.5rem; justify-content:flex-end; margin-top:1rem; }
    .submit-overlay {
      position:fixed; inset:0; background:rgba(75,58,29,0.22); z-index:120;
      display:flex; align-items:center; justify-content:center; padding:1rem;
    }
    .submit-overlay-inner {
      background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.5rem;
      max-width:320px; text-align:center; box-shadow:0 14px 40px rgba(101,76,36,0.15);
    }
    .submit-dots { display:flex; gap:0.35rem; justify-content:center; margin-top:0.75rem; }
    .submit-dots span {
      width:8px; height:8px; border-radius:50%; background:var(--accent);
      animation: dotBounce .55s ease-in-out infinite both;
    }
    .submit-dots span:nth-child(2) { animation-delay: .1s; }
    .submit-dots span:nth-child(3) { animation-delay: .2s; }
    @keyframes dotBounce { 0%, 100% { transform: translateY(0); opacity:0.45; } 50% { transform: translateY(-6px); opacity:1; } }
    .success-backdrop {
      position:fixed; inset:0; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:130;
    }
    .success-modal {
      background:var(--panel); border-radius:16px; border:1px solid var(--border); padding:1.2rem 1.4rem 1.3rem;
      text-align:center; width:min(320px, 90%); box-shadow:0 18px 45px rgba(0,0,0,0.5);
    }
    .success-check {
      width:56px; height:56px; border-radius:50%; border:3px solid rgba(52,199,89,0.25);
      margin:0 auto 0.6rem; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;
    }
    .success-check::before {
      content:''; width:28px; height:14px; border-left:3px solid var(--green); border-bottom:3px solid var(--green);
      transform: rotate(-45deg) scale(0); transform-origin:center;
      animation: check-draw 320ms ease-out forwards 120ms;
    }
    @media (max-width: 720px) { .sidebar { display:none; } main { padding-top:0.5rem; } }
  </style>
</head>
<body class="page-enter">
  <aside class="sidebar">
    <div>
      <img src="../assets/sys_img/pyxfoodpr.png" alt="Pyxfood logo" style="height:42px;width:auto;border-radius:8px;margin-bottom:0.5rem;" onerror="this.style.display='none'">
      <h1>Pyx Tracker</h1>
      <p class="muted" style="margin:0.25rem 0 0;font-size:0.78rem;">Pre‑dispatch photos</p>
    </div>
    <nav class="nav-links">
      <a href="index.php">Add deliveries</a>
      <?php if ($isAdmin): ?>
        <a href="review.php">Review batches (admin)</a>
        <a href="../dashboard.php">Dashboard</a>
      <?php elseif ($isLoggedIn && (($user['role'] ?? '') === RTS_ROLE_TEAM_LEADER || ($user['role'] ?? '') === RTS_ROLE_STAFF)): ?>
        <a href="../portal.php">Portal</a>
      <?php endif; ?>
      <a href="../settings.php">Settings</a>
      <a href="../logout.php">Sign out</a>
    </nav>
  </aside>
  <main>
    <div class="card">
      <p class="muted" style="margin:0 0 1rem;">Pre-dispatch parcel photos for <strong><?= htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8') ?></strong>. All parcels scanned today are saved in one batch for this date. Tap <strong>Add deliveries</strong> to scan a label, capture the parcel photo, then continue or scan the next parcel.</p>
      <input type="hidden" id="delivery_date" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>">
      <button type="button" class="btn btn-block" id="btnAddDeliveries">Add deliveries</button>
      <div id="saveErrorBanner" role="alert" aria-live="assertive"></div>
      <p class="muted" id="batchStatus" style="margin:0.75rem 0 0;font-size:0.82rem;" aria-live="polite"></p>
      <?php if ($isLoggedIn): ?>
        <p class="muted" style="margin:0.5rem 0 0;font-size:0.78rem;">Signed in as <strong><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.</p>
      <?php endif; ?>
      <div id="result" class="muted"></div>
    </div>
  </main>

  <input type="file" id="parcelPhotoInput" class="hidden" accept="image/*" capture="environment" aria-hidden="true" tabindex="-1">

  <div id="scannerModal" class="overlay-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="scannerModalTitle">
    <div class="overlay-panel">
      <div class="overlay-header">
        <h3 id="scannerModalTitle" style="margin:0;">Scan label</h3>
        <button type="button" class="overlay-close" id="scannerModalClose" aria-label="Close scanner">&times;</button>
      </div>
      <p class="muted" style="margin:0 0 0.65rem;font-size:0.85rem;">Point at the parcel QR or barcode. Tracking is read on this device.</p>
      <div id="reader"></div>
    </div>
  </div>

  <div id="previewModal" class="overlay-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="previewModalTitle">
    <div class="overlay-panel">
      <h3 id="previewModalTitle">Parcel photo</h3>
      <label style="font-size:0.8rem;color:var(--muted);">Tracking number</label>
      <div id="modalTracking" class="tracking-display" aria-live="polite"></div>
      <div id="captureFallback" class="capture-fallback hidden">
        <strong>Tap below</strong> to open the camera and photograph the parcel.
        <button type="button" class="btn btn-block" id="btnCaptureFallback" style="margin-top:0.5rem;">Take parcel photo</button>
      </div>
      <div id="parcelPreviewWrap" class="parcel-preview-wrap hidden">
        <img id="parcelPreviewImg" src="" alt="Parcel preview">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="previewCancel">Cancel</button>
        <button type="button" class="btn btn-secondary" id="previewDone" disabled>Done</button>
        <button type="button" class="btn" id="previewScanNext" disabled>Scan next parcel</button>
      </div>
    </div>
  </div>

  <div id="submitOverlay" class="submit-overlay hidden" aria-hidden="true">
    <div class="submit-overlay-inner">
      <strong id="submitOverlayTitle" style="font-size:1rem;">Saving</strong>
      <p id="submitOverlayMsg" class="muted" style="margin:0.5rem 0 0;font-size:0.85rem;">Uploading parcel photo…</p>
      <div class="submit-dots" aria-hidden="true"><span></span><span></span><span></span></div>
    </div>
  </div>
  <div id="successBackdrop" class="success-backdrop hidden" aria-hidden="true">
    <div class="success-modal">
      <div class="success-check"></div>
      <p style="margin:0 0 0.25rem;font-weight:600;">Parcel saved</p>
      <p id="successMsg" style="margin:0;font-size:0.85rem;color:var(--muted);"></p>
    </div>
  </div>

  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script>
(function () {
  var dateEl = document.getElementById('delivery_date');
  var result = document.getElementById('result');
  var batchStatus = document.getElementById('batchStatus');
  var btnAddDeliveries = document.getElementById('btnAddDeliveries');
  var readerEl = document.getElementById('reader');
  var scannerModal = document.getElementById('scannerModal');
  var scannerModalClose = document.getElementById('scannerModalClose');
  var previewModal = document.getElementById('previewModal');
  var modalTracking = document.getElementById('modalTracking');
  var parcelPhotoInput = document.getElementById('parcelPhotoInput');
  var captureFallback = document.getElementById('captureFallback');
  var btnCaptureFallback = document.getElementById('btnCaptureFallback');
  var parcelPreviewWrap = document.getElementById('parcelPreviewWrap');
  var parcelPreviewImg = document.getElementById('parcelPreviewImg');
  var previewCancel = document.getElementById('previewCancel');
  var previewDone = document.getElementById('previewDone');
  var previewScanNext = document.getElementById('previewScanNext');
  var submitOverlay = document.getElementById('submitOverlay');
  var submitOverlayTitle = document.getElementById('submitOverlayTitle');
  var submitOverlayMsg = document.getElementById('submitOverlayMsg');
  var successBackdrop = document.getElementById('successBackdrop');
  var successMsg = document.getElementById('successMsg');
  var saveErrorBanner = document.getElementById('saveErrorBanner');
  var api = '../api/delivery_bulk.php?ngrok-skip-browser-warning=1';
  var isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

  var html5QrcodeScanner = null;
  var scanLock = false;
  var pendingTracking = '';
  var pendingParcelFile = null;
  var parcelPreviewUrl = null;
  var sessionBatchId = null;
  var sessionBatchCount = 0;
  var saveInFlight = false;

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function ngrokFetchHeaders() {
    return { 'ngrok-skip-browser-warning': '1' };
  }

  function looksLikeHtmlBody(raw) {
    var t = String(raw || '').trim().toLowerCase();
    return t.indexOf('<!doctype') === 0 || t.indexOf('<html') === 0;
  }

  function showSaveError(msg) {
    if (saveErrorBanner) {
      saveErrorBanner.textContent = msg;
      saveErrorBanner.classList.add('visible');
    }
    if (result) {
      result.innerHTML = '<span class="pill bad">Error</span> ' + escapeHtml(msg);
    }
  }

  function clearSaveError() {
    if (saveErrorBanner) {
      saveErrorBanner.textContent = '';
      saveErrorBanner.classList.remove('visible');
    }
  }

  function normalizeTrackingFromQr(raw) {
    var t = String(raw || '').trim();
    if (!t) return '';
    if (/^https?:\/\//i.test(t)) {
      try {
        var u = new URL(t);
        var parts = (u.pathname || '').split('/').filter(Boolean);
        var seg = parts.length ? parts[parts.length - 1] : '';
        if (seg && seg !== '/') t = decodeURIComponent(seg);
        var tr = u.searchParams.get('tracking') || u.searchParams.get('Tracking');
        if (tr) t = decodeURIComponent(tr);
      } catch (e) { /* keep t */ }
    }
    return t.trim();
  }

  function getScannerFormats() {
    return [
      Html5QrcodeSupportedFormats.QR_CODE,
      Html5QrcodeSupportedFormats.EAN_13,
      Html5QrcodeSupportedFormats.EAN_8,
      Html5QrcodeSupportedFormats.CODE_128,
      Html5QrcodeSupportedFormats.CODE_39,
      Html5QrcodeSupportedFormats.UPC_A,
      Html5QrcodeSupportedFormats.UPC_E
    ];
  }

  function scannerConfig() {
    return {
      fps: 20,
      qrbox: function (viewfinderWidth, viewfinderHeight) {
        return {
          width: Math.min(viewfinderWidth * 0.8, 300),
          height: Math.min(viewfinderHeight * 0.5, 200)
        };
      },
      rememberLastUsedCamera: true,
      formatsToSupport: getScannerFormats()
    };
  }

  function updateBatchStatus() {
    if (!batchStatus) return;
    if (sessionBatchId) {
      batchStatus.textContent = "Today's batch #" + sessionBatchId + ' — ' + sessionBatchCount + ' parcel(s) saved.';
    } else {
      batchStatus.textContent = '';
    }
  }

  function showSubmitOverlay(on, title, msg) {
    if (!submitOverlay) return;
    if (title && submitOverlayTitle) submitOverlayTitle.textContent = title;
    if (msg && submitOverlayMsg) submitOverlayMsg.textContent = msg;
    submitOverlay.classList.toggle('hidden', !on);
    submitOverlay.setAttribute('aria-hidden', on ? 'false' : 'true');
  }

  function showSuccessCheck(msg) {
    if (!successBackdrop) return;
    if (successMsg && msg) successMsg.textContent = msg;
    successBackdrop.classList.remove('hidden');
    successBackdrop.setAttribute('aria-hidden', 'false');
    clearTimeout(showSuccessCheck._tm);
    showSuccessCheck._tm = setTimeout(function () {
      successBackdrop.classList.add('hidden');
      successBackdrop.setAttribute('aria-hidden', 'true');
    }, 1400);
  }

  function revokeParcelPreview() {
    if (parcelPreviewUrl) {
      try { URL.revokeObjectURL(parcelPreviewUrl); } catch (e) {}
      parcelPreviewUrl = null;
    }
  }

  function setPreviewActionsEnabled(on) {
    if (previewDone) previewDone.disabled = !on;
    if (previewScanNext) previewScanNext.disabled = !on;
  }

  function resetPreviewState() {
    pendingTracking = '';
    pendingParcelFile = null;
    if (parcelPhotoInput) parcelPhotoInput.value = '';
    revokeParcelPreview();
    if (parcelPreviewWrap) parcelPreviewWrap.classList.add('hidden');
    if (parcelPreviewImg) parcelPreviewImg.removeAttribute('src');
    if (captureFallback) captureFallback.classList.add('hidden');
    if (modalTracking) modalTracking.textContent = '';
    setPreviewActionsEnabled(false);
  }

  function hideScannerModal() {
    if (scannerModal) {
      scannerModal.classList.add('hidden');
      scannerModal.setAttribute('aria-hidden', 'true');
    }
    void clearScanner();
  }

  function hidePreviewModal() {
    if (previewModal) {
      previewModal.classList.add('hidden');
      previewModal.setAttribute('aria-hidden', 'true');
    }
    resetPreviewState();
  }

  function showScannerModal() {
    if (previewModal) {
      previewModal.classList.add('hidden');
      previewModal.setAttribute('aria-hidden', 'true');
    }
    resetPreviewState();
    if (scannerModal) {
      scannerModal.classList.remove('hidden');
      scannerModal.setAttribute('aria-hidden', 'false');
    }
    startScanner();
  }

  function openPreviewForTracking(tracking) {
    pendingTracking = tracking;
    if (modalTracking) modalTracking.textContent = tracking;
    hideScannerModal();
    if (previewModal) {
      previewModal.classList.remove('hidden');
      previewModal.setAttribute('aria-hidden', 'false');
    }
    setPreviewActionsEnabled(false);
    if (parcelPreviewWrap) parcelPreviewWrap.classList.add('hidden');
    if (captureFallback) captureFallback.classList.add('hidden');
    triggerParcelCamera();
  }

  function triggerParcelCamera() {
    if (!parcelPhotoInput) return;
    parcelPhotoInput.value = '';
    try {
      parcelPhotoInput.click();
    } catch (e) { /* ignore */ }
    setTimeout(function () {
      if (!pendingParcelFile && captureFallback && previewModal && !previewModal.classList.contains('hidden')) {
        captureFallback.classList.remove('hidden');
      }
    }, 450);
  }

  async function clearScanner() {
    if (!html5QrcodeScanner) return;
    var inst = html5QrcodeScanner;
    html5QrcodeScanner = null;
    try {
      await inst.clear();
    } catch (e) { /* ignore */ }
    if (readerEl) readerEl.innerHTML = '';
  }

  function startScanner() {
    if (typeof Html5QrcodeScanner === 'undefined') {
      if (result) result.innerHTML = '<span class="pill bad">Error</span> Scanner library failed to load.';
      return;
    }
    if (!readerEl) return;
    void clearScanner().then(function () {
      scanLock = false;
      html5QrcodeScanner = new Html5QrcodeScanner('reader', scannerConfig(), false);
      html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    });
  }

  function onScanFailure() {}

  function onScanSuccess(decodedText) {
    if (scanLock) return;
    var tracking = normalizeTrackingFromQr(decodedText);
    if (!tracking) return;
    scanLock = true;
    openPreviewForTracking(tracking);
  }

  function onParcelPhotoSelected(file) {
    if (!file || !pendingTracking) return;
    pendingParcelFile = file;
    revokeParcelPreview();
    parcelPreviewUrl = URL.createObjectURL(file);
    if (parcelPreviewImg) parcelPreviewImg.src = parcelPreviewUrl;
    if (parcelPreviewWrap) parcelPreviewWrap.classList.remove('hidden');
    if (captureFallback) captureFallback.classList.add('hidden');
    setPreviewActionsEnabled(true);
  }

  function saveParcel(andThen) {
    if (!pendingTracking || !pendingParcelFile) {
      showSaveError('Missing tracking or photo. Take the parcel photo again.');
      return Promise.resolve(false);
    }
    if (!dateEl || !dateEl.value) {
      showSaveError('Delivery date missing.');
      return Promise.resolve(false);
    }
    if (saveInFlight) return Promise.resolve(false);
    saveInFlight = true;
    clearSaveError();
    showSubmitOverlay(true, 'Saving', 'Uploading parcel photo…');
    setPreviewActionsEnabled(false);

    var fileToSend = pendingParcelFile;

    return (async function () {
      try {
        var buf = await fileToSend.arrayBuffer();
        var type = fileToSend.type || 'image/jpeg';
        fileToSend = new File([buf], fileToSend.name || 'parcel.jpg', { type: type, lastModified: Date.now() });
      } catch (e) {
        saveInFlight = false;
        showSubmitOverlay(false);
        showSaveError('Could not read the photo on this device. Try again.');
        setPreviewActionsEnabled(true);
        return false;
      }

      var fd = new FormData();
      fd.append('delivery_date', dateEl.value);
      fd.append('photos[]', fileToSend, fileToSend.name || 'parcel.jpg');
      fd.append('client_hints_json', JSON.stringify([pendingTracking]));

      var res;
      try {
        var r = await fetch(api, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: ngrokFetchHeaders()
        });
        var raw = await r.text();
        var ct = (r.headers.get('content-type') || '').toLowerCase();
        res = { status: r.status, raw: raw, contentType: ct };
      } catch (e) {
        saveInFlight = false;
        showSubmitOverlay(false);
        showSaveError('Network error. Check connection and try again.');
        setPreviewActionsEnabled(true);
        return false;
      }

      showSubmitOverlay(false);
      saveInFlight = false;
      var xj = null;
      try { xj = JSON.parse(res.raw); } catch (e) { xj = null; }

      if (!xj) {
        var hint;
        var snippet = String(res.raw || '').trim().substring(0, 120).replace(/\s+/g, ' ');
        if (looksLikeHtmlBody(res.raw) || res.contentType.indexOf('json') === -1) {
          hint = 'Server did not return JSON (often ngrok or a PHP error). Refresh and try again.';
        } else if (res.status === 413) {
          hint = 'Upload too large for the server.';
        } else {
          hint = 'Unexpected response (HTTP ' + res.status + ').';
        }
        if (snippet) {
          hint += ' Response: ' + snippet;
        }
        showSaveError(hint);
        setPreviewActionsEnabled(true);
        return false;
      }

      if (!xj.ok) {
        showSaveError(xj.error ? String(xj.error) : 'Upload failed');
        setPreviewActionsEnabled(true);
        return false;
      }

      if (!xj.batch_id && !(xj.items && xj.items.length)) {
        showSaveError('Server said OK but returned no batch data. Check PHP error logs.');
        setPreviewActionsEnabled(true);
        return false;
      }

      sessionBatchId = xj.batch_id || sessionBatchId;
      sessionBatchCount = xj.batch_item_count != null ? xj.batch_item_count : sessionBatchCount;
      updateBatchStatus();
      var item = (xj.items && xj.items[0]) ? xj.items[0] : {};
      var tn = item.tracking_number || pendingTracking;
      clearSaveError();
      showSuccessCheck('Saved ' + tn + '.');
      if (result) {
        var html = '<span class="pill ok">Saved</span> <code>' + escapeHtml(tn) + '</code>';
        if (sessionBatchId) html += ' <span class="muted">(batch #' + escapeHtml(String(sessionBatchId)) + ')</span>';
        if (isAdmin) {
          html += '<br><a class="btn btn-secondary" style="text-decoration:none;display:inline-flex;margin-top:0.5rem;font-size:0.85rem;" href="review.php">Open in review</a>';
        }
        result.innerHTML = html;
      }
      hidePreviewModal();
      scanLock = false;
      if (typeof andThen === 'function') {
        andThen();
      }
      return true;
    })();
  }

  if (parcelPhotoInput) {
    parcelPhotoInput.addEventListener('change', function () {
      var f = parcelPhotoInput.files && parcelPhotoInput.files[0];
      if (!f) {
        if (pendingTracking && !pendingParcelFile) {
          scanLock = false;
          showScannerModal();
        }
        return;
      }
      onParcelPhotoSelected(f);
    });
  }

  if (btnCaptureFallback) {
    btnCaptureFallback.addEventListener('click', function () {
      if (captureFallback) captureFallback.classList.add('hidden');
      triggerParcelCamera();
    });
  }

  if (btnAddDeliveries) {
    btnAddDeliveries.addEventListener('click', function () {
      result.innerHTML = '';
      scanLock = false;
      showScannerModal();
    });
  }

  if (scannerModalClose) {
    scannerModalClose.addEventListener('click', function () {
      hideScannerModal();
      scanLock = false;
    });
  }

  if (previewCancel) {
    previewCancel.addEventListener('click', function () {
      hidePreviewModal();
      scanLock = false;
      showScannerModal();
    });
  }

  if (previewDone) {
    previewDone.addEventListener('click', function () {
      saveParcel(function () {
        hideScannerModal();
      });
    });
  }

  if (previewScanNext) {
    previewScanNext.addEventListener('click', function () {
      if (previewScanNext.disabled) return;
      saveParcel(function () {
        showScannerModal();
      });
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (previewModal && !previewModal.classList.contains('hidden')) {
      hidePreviewModal();
      scanLock = false;
      return;
    }
    if (scannerModal && !scannerModal.classList.contains('hidden')) {
      hideScannerModal();
      scanLock = false;
    }
  });

  document.querySelectorAll('a[href$=".php"], a[href*=".php?"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var href = a.getAttribute('href');
      if (!href || href.indexOf('#') === 0) return;
      e.preventDefault();
      document.body.classList.add('page-exit');
      setTimeout(function () { window.location.href = href; }, 180);
    });
  });
})();
  </script>
</body>
</html>
