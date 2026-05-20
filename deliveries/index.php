<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

rts_session_start();
rts_require_roles([
    RTS_ROLE_EXECUTIVE,
    RTS_ROLE_PRODUCTION_MANAGER,
    RTS_ROLE_TEAM_LEADER,
    RTS_ROLE_STAFF,
    RTS_ROLE_DELIVERY,
    RTS_ROLE_ADMIN,
], '../login.php');
$user = rts_auth_user();

$pageTitle = 'Pyx Tracker — deliveries bulk upload';
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$isAdmin = rts_can_access_rts_admin($user);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
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
    label { display:block; font-size:0.8rem; color:var(--muted); margin-bottom:0.25rem; }
    input[type="date"], input[type="file"] { width:100%; padding:0.45rem; border-radius:8px; border:1px solid var(--border); background:#fffdfa; }
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:0.55rem 1.1rem; border-radius:10px; border:none; background:var(--accent); color:#061018; font-weight:700; cursor:pointer; font-size:0.95rem; }
    .btn:disabled { opacity:0.55; cursor:not-allowed; }
    .btn-secondary { background:transparent; border:1px solid var(--border); color:var(--text); font-weight:500; }
    .muted { color:var(--muted); font-size:0.88rem; }
    #result { font-size:0.88rem; margin-top:0.75rem; }
    .pill { display:inline-block; padding:0.12rem 0.45rem; border-radius:6px; background:#fff7ea; border:1px solid var(--border); font-size:0.8rem; margin:0.15rem 0.15rem 0 0; }
    .pill.warn { border-color:#c9a227; color:#6a5200; }
    .pill.bad { border-color:var(--danger); color:var(--danger); }
    .pill.ok { border-color:var(--ok); color:var(--ok); }
    .hidden { display:none !important; }
    .submit-overlay {
      position:fixed; inset:0; background:rgba(75,58,29,0.22); z-index:70;
      display:flex; align-items:center; justify-content:center; padding:1rem;
      animation: fadeIn .2s ease both;
    }
    .submit-overlay-inner {
      background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.5rem;
      max-width:320px; text-align:center; box-shadow:0 14px 40px rgba(101,76,36,0.15);
      animation: popIn .28s ease both;
    }
    .submit-dots { display:flex; gap:0.35rem; justify-content:center; margin-top:0.75rem; }
    .submit-dots span {
      width:8px; height:8px; border-radius:50%; background:var(--accent);
      animation: dotBounce .55s ease-in-out infinite both;
    }
    .submit-dots span:nth-child(2) { animation-delay: .1s; }
    .submit-dots span:nth-child(3) { animation-delay: .2s; }
    @keyframes dotBounce { 0%, 100% { transform: translateY(0); opacity:0.45; } 50% { transform: translateY(-6px); opacity:1; } }
    .preview-card { margin-top:1rem; padding-top:1rem; border-top:1px solid var(--border); animation: fadeIn .25s ease both; }
    .preview-card h3 { margin:0 0 0.65rem; font-size:0.95rem; font-weight:650; color:var(--text); }
    .preview-grid-wrap {
      max-height: min(52vh, 440px);
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 0.5rem;
      background: #fffdfa;
    }
    .preview-grid {
      display:grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap:0.75rem;
    }
    .preview-item {
      display:flex; gap:0.75rem; align-items:flex-start; padding:0.55rem 0.65rem; border:1px solid var(--border); border-radius:10px; background:#fffdfa;
    }
    .preview-item.qr-fail { border-color:#e0a090; background:#fff6f3; }
    .preview-thumb-wrap {
      flex:0 0 104px; width:104px; height:104px; border-radius:8px; overflow:hidden; border:1px solid var(--border); background:#f5efe3;
    }
    .preview-thumb-wrap img { width:100%; height:100%; object-fit:cover; display:block; }
    .preview-meta { flex:1; min-width:0; font-size:0.82rem; }
    .preview-filename { font-weight:600; word-break:break-all; color:var(--text); }
    .preview-qr-status { margin-top:0.35rem; line-height:1.35; }
    .preview-qr-status.scanning { color:var(--muted); font-style:italic; }
    .preview-qr-status.ok { color:var(--ok); }
    .preview-qr-status.warn { color:#8a6d00; }
    .preview-actions { margin-top:1rem; display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; }
    .confirm-modal-backdrop {
      position:fixed; inset:0; background:rgba(75,58,29,0.4); z-index:100;
      display:flex; align-items:center; justify-content:center; padding:1rem;
      animation: fadeIn .2s ease both;
    }
    .confirm-modal {
      background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:1.2rem 1.35rem;
      max-width:420px; width:100%; box-shadow:0 16px 48px rgba(101,76,36,0.2);
      animation: popIn .28s ease both;
    }
    .confirm-modal h3 { margin:0 0 0.65rem; font-size:1.05rem; }
    .confirm-modal .summary { font-size:0.9rem; line-height:1.5; margin:0 0 1rem; }
    .success-backdrop {
      position:fixed; inset:0; background:rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:80;
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
    .success-check::after {
      content:''; position:absolute; inset:-1px; border-radius:50%; border:3px solid rgba(52,199,89,0.45);
      transform: scale(0.4); opacity:0; animation: check-pulse 480ms ease-out forwards;
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
      <a href="index.php">Upload batch</a>
      <?php if ($isAdmin): ?>
        <a href="review.php">Review batches (admin)</a>
        <a href="../dashboard.php">Dashboard</a>
      <?php elseif (($user['role'] ?? '') === RTS_ROLE_TEAM_LEADER || ($user['role'] ?? '') === RTS_ROLE_STAFF): ?>
        <a href="../portal.php">Portal</a>
      <?php endif; ?>
      <a href="../settings.php">Settings</a>
      <a href="../logout.php">Sign out</a>
    </nav>
  </aside>
  <main>
    <div class="card">
      <p class="muted" style="margin:0 0 1rem;">Select up to <strong>30</strong> parcel photos (up to <strong>20 MB</strong> each) and the <strong>delivery date</strong> for this run. After you pick photos, a <strong>preview</strong> appears with QR status for each image — then <strong>confirm</strong> to submit. QR codes are read the same way as RTS (server first, then in-browser scanner). Very large batches may still be capped by the server’s total upload limit (raise <code>post_max_size</code> and <code>upload_max_filesize</code> in PHP if needed).</p>
      <form id="bulkForm">
        <label for="delivery_date">Delivery date (for this batch)</label>
        <input type="date" id="delivery_date" name="delivery_date" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="photos" style="margin-top:0.85rem;">Photos (JPEG / PNG / WebP / GIF)</label>
        <input type="file" id="photos" name="photos[]" accept="image/*" multiple required>
        <p class="muted" style="margin:0.5rem 0 0;">Signed in as <strong><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>). This account will be saved as the submitter of this batch.</p>
        <div id="previewCard" class="preview-card hidden" aria-live="polite">
          <h3>Review selected photos</h3>
          <p class="muted" style="margin:0 0 0.75rem;font-size:0.82rem;">Check each image. When you are ready, confirm to upload this batch.</p>
          <div class="preview-grid-wrap">
            <div id="previewGrid" class="preview-grid"></div>
          </div>
          <div class="preview-actions">
            <button type="button" class="btn" id="btnConfirmUpload" disabled>Review &amp; confirm upload…</button>
            <button type="button" class="btn btn-secondary" id="btnReselectPhotos">Choose different photos</button>
          </div>
        </div>
        <p class="muted" id="selectHint" style="margin:0.75rem 0 0;font-size:0.82rem;">After you pick photos, a preview appears here — then confirm to send the batch.</p>
      </form>
      <div id="result" class="muted"></div>
    </div>
  </main>
  <div id="qrWorkReader" style="position:fixed;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;top:0;left:0;" aria-hidden="true"></div>
  <div id="submitOverlay" class="submit-overlay hidden" aria-hidden="true">
    <div class="submit-overlay-inner">
      <strong id="submitOverlayTitle" style="font-size:1rem;">Uploading</strong>
      <p id="submitOverlayMsg" class="muted" style="margin:0.5rem 0 0;font-size:0.85rem;">Sending your batch…</p>
      <div class="submit-dots" aria-hidden="true"><span></span><span></span><span></span></div>
    </div>
  </div>
  <div id="confirmModal" class="confirm-modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
    <div class="confirm-modal">
      <h3 id="confirmModalTitle">Submit this batch?</h3>
      <p id="confirmModalSummary" class="summary muted"></p>
      <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:flex-end;">
        <button type="button" class="btn btn-secondary" id="confirmModalCancel">Cancel</button>
        <button type="button" class="btn" id="confirmModalSubmit">Yes, upload now</button>
      </div>
    </div>
  </div>
  <div id="successBackdrop" class="success-backdrop hidden" aria-hidden="true">
    <div class="success-modal">
      <div class="success-check"></div>
      <p style="margin:0 0 0.25rem;font-weight:600;">Batch saved</p>
      <p style="margin:0;font-size:0.85rem;color:var(--muted);">Your delivery photos were uploaded.</p>
    </div>
  </div>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script>
(function () {
  var form = document.getElementById('bulkForm');
  var photos = document.getElementById('photos');
  var dateEl = document.getElementById('delivery_date');
  var result = document.getElementById('result');
  var previewCard = document.getElementById('previewCard');
  var previewGrid = document.getElementById('previewGrid');
  var btnConfirmUpload = document.getElementById('btnConfirmUpload');
  var btnReselectPhotos = document.getElementById('btnReselectPhotos');
  var selectHint = document.getElementById('selectHint');
  var submitOverlay = document.getElementById('submitOverlay');
  var submitOverlayTitle = document.getElementById('submitOverlayTitle');
  var submitOverlayMsg = document.getElementById('submitOverlayMsg');
  var successBackdrop = document.getElementById('successBackdrop');
  var confirmModal = document.getElementById('confirmModal');
  var confirmModalSummary = document.getElementById('confirmModalSummary');
  var confirmModalCancel = document.getElementById('confirmModalCancel');
  var confirmModalSubmit = document.getElementById('confirmModalSubmit');
  var api = '../api/delivery_bulk.php';
  var qrDecodeApi = '../api/scan_qr_image_public.php';
  var isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

  var previewObjectUrls = [];
  var cachedFiles = null;
  var cachedHints = null;
  var previewRunId = 0;

  function showSubmitOverlay(on, title, msg) {
    if (!submitOverlay) return;
    if (title && submitOverlayTitle) submitOverlayTitle.textContent = title;
    if (msg && submitOverlayMsg) submitOverlayMsg.textContent = msg;
    submitOverlay.classList.toggle('hidden', !on);
    submitOverlay.setAttribute('aria-hidden', on ? 'false' : 'true');
  }

  function showSuccessCheck() {
    if (!successBackdrop) return;
    successBackdrop.classList.remove('hidden');
    successBackdrop.setAttribute('aria-hidden', 'false');
    clearTimeout(showSuccessCheck._tm);
    showSuccessCheck._tm = setTimeout(function () {
      successBackdrop.classList.add('hidden');
      successBackdrop.setAttribute('aria-hidden', 'true');
    }, 1600);
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  async function tryServerQrDecode(file) {
    try {
      var fd = new FormData();
      fd.append('image', file);
      var r = await fetch(qrDecodeApi, { method: 'POST', body: fd, credentials: 'same-origin' });
      if (!r.ok) return null;
      var j = await r.json();
      if (j && j.ok && j.text) return String(j.text);
    } catch (e) {
      return null;
    }
    return null;
  }

  async function clientQrHintFromFile(file) {
    var serverText = await tryServerQrDecode(file);
    if (serverText) return serverText;
    if (typeof Html5Qrcode === 'undefined') return '';
    var h = new Html5Qrcode('qrWorkReader');
    try {
      var text = await h.scanFile(file, false);
      await h.clear();
      return text ? String(text).trim() : '';
    } catch (err) {
      try { await h.clear(); } catch (e2) {}
      return '';
    }
  }

  function revokePreviewUrls() {
    previewObjectUrls.forEach(function (u) {
      try { URL.revokeObjectURL(u); } catch (e) {}
    });
    previewObjectUrls = [];
  }

  async function stashFiles(fileList) {
    var out = [];
    for (var i = 0; i < fileList.length; i++) {
      var f = fileList[i];
      try {
        var buf = await f.arrayBuffer();
        var type = f.type || 'image/jpeg';
        var ext = type.indexOf('png') >= 0 ? 'png' : (type.indexOf('webp') >= 0 ? 'webp' : 'jpg');
        var name = f.name || ('photo_' + (i + 1) + '.' + ext);
        out.push(new File([buf], name, { type: type, lastModified: f.lastModified || Date.now() }));
      } catch (e) {
        throw new Error('Could not read "' + (f.name || ('photo ' + (i + 1))) + '". Try selecting again or save the image on this device first.');
      }
    }
    return out;
  }

  function clearPhotoSelection() {
    previewRunId++;
    revokePreviewUrls();
    cachedFiles = null;
    cachedHints = null;
    photos.value = '';
    if (previewGrid) previewGrid.innerHTML = '';
    if (previewCard) previewCard.classList.add('hidden');
    if (btnConfirmUpload) btnConfirmUpload.disabled = true;
    if (selectHint) selectHint.classList.remove('hidden');
  }

  function setRowQrStatus(row, hint) {
    var el = row.querySelector('.preview-qr-status');
    if (!el) return;
    var ok = hint && String(hint).trim() !== '';
    el.classList.remove('scanning', 'ok', 'warn');
    row.classList.toggle('qr-fail', !ok);
    if (ok) {
      el.classList.add('ok');
      var t = String(hint).trim();
      el.innerHTML = 'QR read successfully.' + (t.length > 80 ? '' : '<br><span class="muted" style="font-size:0.78rem;word-break:break-all;">' + escapeHtml(t) + '</span>');
    } else {
      el.classList.add('warn');
      el.innerHTML = '<strong>The image QR could not be scanned.</strong><br><span class="muted" style="font-size:0.78rem;">You can still submit — staff can enter tracking in Deliveries review.</span>';
    }
  }

  async function runPhotoPreview(files) {
    var myRun = ++previewRunId;
    revokePreviewUrls();
    if (!previewGrid || !previewCard) return;
    previewGrid.innerHTML = '';
    cachedHints = null;
    if (btnConfirmUpload) btnConfirmUpload.disabled = true;
    previewCard.classList.remove('hidden');
    if (selectHint) selectHint.classList.add('hidden');

    var rows = [];
    for (var i = 0; i < files.length; i++) {
      if (myRun !== previewRunId) return;
      var f = files[i];
      var url = URL.createObjectURL(f);
      previewObjectUrls.push(url);
      var row = document.createElement('div');
      row.className = 'preview-item';
      row.innerHTML =
        '<div class="preview-thumb-wrap"><img src="' + url + '" alt="' + escapeHtml(f.name || 'Selected photo') + '"></div>' +
        '<div class="preview-meta">' +
        '<div class="preview-filename">' + escapeHtml(f.name || 'Photo ' + (i + 1)) + '</div>' +
        '<div class="preview-qr-status scanning">Scanning for QR…</div>' +
        '</div>';
      previewGrid.appendChild(row);
      rows.push(row);
    }

    var hints = [];
    for (var j = 0; j < files.length; j++) {
      if (myRun !== previewRunId) return;
      var h = await clientQrHintFromFile(files[j]);
      hints.push(h);
      if (myRun !== previewRunId) return;
      setRowQrStatus(rows[j], h);
    }
    if (myRun !== previewRunId) return;
    cachedHints = hints;
    if (btnConfirmUpload) btnConfirmUpload.disabled = false;
  }

  photos.addEventListener('change', function () {
    result.innerHTML = '';
    var picked = photos.files;
    if (!picked || picked.length < 1) {
      clearPhotoSelection();
      return;
    }
    if (picked.length > 30) {
      result.textContent = 'Maximum 30 images per batch.';
      clearPhotoSelection();
      return;
    }
    (async function () {
      showSubmitOverlay(true, 'Loading photos', 'Reading selected images into memory…');
      try {
        cachedFiles = await stashFiles(picked);
        showSubmitOverlay(false);
        await runPhotoPreview(cachedFiles);
      } catch (err) {
        showSubmitOverlay(false);
        result.innerHTML = '<span class="pill bad">Error</span> ' + escapeHtml(err.message || 'Could not load photos.');
        clearPhotoSelection();
      }
    })();
  });

  if (btnReselectPhotos) btnReselectPhotos.addEventListener('click', function () {
    result.innerHTML = '';
    clearPhotoSelection();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
  });

  function openConfirmModal() {
    result.innerHTML = '';
    var files = cachedFiles;
    if (!files || files.length < 1) {
      result.textContent = 'Choose at least one image.';
      return;
    }
    if (files.length > 30) {
      result.textContent = 'Maximum 30 images per batch.';
      return;
    }
    if (!dateEl.value) {
      result.textContent = 'Choose a delivery date.';
      return;
    }
    var hints = cachedHints;
    if (!hints || hints.length !== files.length) {
      result.textContent = 'Please wait for the preview to finish scanning QR codes, or choose photos again.';
      return;
    }
    var fail = 0;
    for (var i = 0; i < hints.length; i++) {
      if (!hints[i] || String(hints[i]).trim() === '') fail++;
    }
    if (confirmModalSummary) {
      var lines = [];
      lines.push('You are about to upload <strong>' + files.length + '</strong> photo' + (files.length === 1 ? '' : 's') + ' for delivery date <strong>' + escapeHtml(dateEl.value) + '</strong>.');
      if (fail > 0) {
        lines.push('<span style="color:#8a4518;">For <strong>' + fail + '</strong> image' + (fail === 1 ? '' : 's') + ', the QR could not be read in the preview.</span> You can still upload; tracking can be added later in review.');
      } else {
        lines.push('A QR string was detected for every image in the preview.');
      }
      lines.push('This cannot be undone from this screen (wrong photos can be voided in admin review).');
      confirmModalSummary.innerHTML = lines.join('<br><br>');
    }
    if (confirmModal) {
      confirmModal.classList.remove('hidden');
      confirmModal.setAttribute('aria-hidden', 'false');
    }
    if (confirmModalSubmit) confirmModalSubmit.focus();
  }

  function closeConfirmModal() {
    if (confirmModal) {
      confirmModal.classList.add('hidden');
      confirmModal.setAttribute('aria-hidden', 'true');
    }
  }

  if (confirmModalCancel) confirmModalCancel.addEventListener('click', closeConfirmModal);
  if (confirmModal) confirmModal.addEventListener('click', function (e) {
    if (e.target === confirmModal) closeConfirmModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (confirmModal && !confirmModal.classList.contains('hidden')) closeConfirmModal();
  });

  if (btnConfirmUpload) btnConfirmUpload.addEventListener('click', openConfirmModal);

  if (confirmModalSubmit) {
    confirmModalSubmit.addEventListener('click', function () {
      closeConfirmModal();
      result.innerHTML = '';
      var files = cachedFiles;
      if (!files || files.length < 1) {
        result.innerHTML = '<span class="pill bad">Error</span> Photos are no longer available. Please choose them again.';
        return;
      }
      var hints = cachedHints;
      if (!hints || hints.length !== files.length) {
        result.innerHTML = '<span class="pill bad">Error</span> Please wait for QR preview to finish, or re-select photos.';
        return;
      }

      btnConfirmUpload.disabled = true;
      if (confirmModalSubmit) confirmModalSubmit.disabled = true;
      showSubmitOverlay(true, 'Uploading', 'Sending your batch and saving photos…');

      (async function () {
        var fd = new FormData();
        fd.append('delivery_date', dateEl.value);
        for (var j = 0; j < files.length; j++) {
          fd.append('photos[]', files[j], files[j].name || ('photo_' + j + '.jpg'));
        }
        fd.append('client_hints_json', JSON.stringify(hints));
        try {
          var r = await fetch(api, { method: 'POST', body: fd, credentials: 'same-origin' });
          var raw = await r.text();
          var xj = null;
          try { xj = JSON.parse(raw); } catch (parseErr) { xj = null; }
          showSubmitOverlay(false);
          btnConfirmUpload.disabled = false;
          if (confirmModalSubmit) confirmModalSubmit.disabled = false;
          if (!xj) {
            var hint = r.status === 413 ? 'Upload too large — try fewer photos or smaller files.' : ('Server returned an unexpected response (HTTP ' + r.status + ').');
            result.innerHTML = '<span class="pill bad">Error</span> ' + escapeHtml(hint);
            return;
          }
          if (!xj.ok) {
            result.innerHTML = '<span class="pill bad">Error</span> ' + escapeHtml(xj.error ? String(xj.error) : 'Upload failed');
            return;
          }
          showSuccessCheck();
          var html = '<span class="pill ok">Batch #' + xj.batch_id + '</span> saved for <strong>' + escapeHtml(xj.delivery_date) + '</strong>.<br><br>';
          (xj.items || []).forEach(function (it) {
            var st = it.item_status;
            var cls = st === 'decoded_ok' ? 'ok' : (st === 'ambiguous' ? 'warn' : 'bad');
            html += '<span class="pill ' + cls + '">' + escapeHtml(st) + '</span> ' + escapeHtml(it.photo_filename || '');
            if (it.tracking_number) html += ' — <code>' + escapeHtml(it.tracking_number) + '</code>';
            html += '<br>';
          });
          if (xj.batch_id && isAdmin) {
            html += '<br><a class="btn btn-secondary" style="text-decoration:none;display:inline-flex;margin-top:0.5rem;" href="review.php">Open in review</a>';
          }
          result.innerHTML = html;
          clearPhotoSelection();
        } catch (err) {
          showSubmitOverlay(false);
          btnConfirmUpload.disabled = false;
          if (confirmModalSubmit) confirmModalSubmit.disabled = false;
          result.innerHTML = '<span class="pill bad">Error</span> Network error. Check your connection and try again.';
        }
      })();
    });
  }

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
