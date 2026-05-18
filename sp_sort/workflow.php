<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$user = sp_sort_user();
$pdo = rts_pdo();
$batchId = isset($_GET['batch']) ? (int) $_GET['batch'] : 0;
if ($batchId < 1) {
    header('Location: index.php', true, 302);
    exit;
}
$batch = sp_sort_batch_or_404($pdo, $batchId);
$isDone = ($batch['status'] ?? '') === 'completed';
$sourceFiles = json_decode((string) ($batch['source_files'] ?? '[]'), true);
if (!is_array($sourceFiles)) {
    $sourceFiles = [];
}
$printMeta = json_decode((string) ($batch['print_meta'] ?? '{}'), true);
if (!is_array($printMeta)) {
    $printMeta = sp_sort_default_print_meta();
} else {
    $printMeta = sp_sort_resolve_print_meta($printMeta);
}
$preparedByDefault = trim((string) ($batch['prepared_by'] ?? ''));
$pageTitle = 'SP Sort — batch #' . $batchId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="sp_sort.css">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar_styles.php'; ?>
  <style>
    .actions { display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:1rem; }
    .confirm-backdrop {
      display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
      z-index:100; align-items:center; justify-content:center; padding:1rem;
    }
    .confirm-backdrop.open { display:flex; }
    .confirm-box {
      background:#fff; border-radius:12px; padding:1.25rem; max-width:400px;
      border:1px solid var(--border); width:100%;
    }
    .export-form label { display:block; font-size:0.78rem; color:var(--muted); margin:0.5rem 0 0.2rem; }
    .export-form input[type="text"] {
      width:100%; padding:0.45rem 0.55rem; border-radius:8px; border:1px solid var(--border);
      font-size:0.9rem; box-sizing:border-box;
    }
    .export-form .req { color:#b91c1c; }
  </style>
</head>
<body class="admin-layout">
<?php rts_admin_sidebar('sp_sort', '../', sp_sort_delivery_nav_badge()); ?>
<main>
<div class="wrap">
  <h1 style="margin:0 0 0.75rem;font-size:1.15rem;">Batch #<?= (int) $batchId ?></h1>

  <div class="steps">
    <span class="step-pill done">1. Merged</span>
    <span class="step-pill active">2. Print &amp; waybill</span>
    <span class="step-pill<?= $isDone ? ' done' : '' ?>">3. Done</span>
  </div>

  <div class="card">
    <p style="margin:0 0 0.5rem;"><strong><?= (int) $batch['row_count'] ?></strong> rows · sorted by column D</p>
    <?php if ($sourceFiles !== []): ?>
      <p style="margin:0;font-size:0.85rem;color:var(--muted);">Files: <?= htmlspecialchars(implode(', ', $sourceFiles), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($isDone): ?>
      <p class="msg msg-success" style="margin-top:0.75rem;">This batch was completed <?= htmlspecialchars((string) $batch['completed_at'], ENT_QUOTES, 'UTF-8') ?>.</p>
    <?php endif; ?>

    <form class="export-form" id="exportForm" method="post" action="api/export.php" style="margin-top:1rem;">
      <input type="hidden" name="batch_id" value="<?= (int) $batchId ?>">
      <label for="prepared_by">Prepared by <span class="req">*</span></label>
      <input type="text" id="prepared_by" name="prepared_by" required maxlength="120"
        placeholder="e.g. Fin, Noli"
        value="<?= htmlspecialchars($preparedByDefault, ENT_QUOTES, 'UTF-8') ?>">

      <label for="order_date_line">Order date line</label>
      <input type="text" id="order_date_line" name="order_date_line" maxlength="120"
        value="<?= htmlspecialchars((string) $printMeta['order_date_line'], ENT_QUOTES, 'UTF-8') ?>">

      <label for="pickup_date_line">Pickup date line</label>
      <input type="text" id="pickup_date_line" name="pickup_date_line" maxlength="120"
        value="<?= htmlspecialchars((string) $printMeta['pickup_date_line'], ENT_QUOTES, 'UTF-8') ?>">

      <label for="note_line_1">Note (top right, row 1)</label>
      <input type="text" id="note_line_1" name="note_line_1" maxlength="200"
        value="<?= htmlspecialchars((string) $printMeta['note_line_1'], ENT_QUOTES, 'UTF-8') ?>">

      <label for="note_line_2">Note (top right, row 2)</label>
      <input type="text" id="note_line_2" name="note_line_2" maxlength="200"
        value="<?= htmlspecialchars((string) $printMeta['note_line_2'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="actions" style="margin-top:1rem;">
        <button type="submit" class="btn btn-primary" name="action" value="download">Download Excel for print</button>
        <button type="submit" class="btn" name="action" value="print" formtarget="_blank">Print sheet (A4)</button>
        <a class="btn" href="carousel.php?batch=<?= (int) $batchId ?>">Carousel mode →</a>
        <?php if (!$isDone): ?>
          <button type="button" class="btn btn-primary" id="btnDone">Mark batch done</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.5rem;font-size:1rem;">Suggested order</h2>
    <ol style="margin:0;padding-left:1.2rem;color:var(--muted);font-size:0.9rem;">
      <li>Enter <strong>Prepared by</strong>, then download or print.</li>
      <li>Download or print the merged sheet for packing.</li>
      <li>Print waybills for the parcels.</li>
      <li>Open <strong>Carousel mode</strong> and use ← → to read each row while writing the waybill.</li>
      <li>When finished, click <strong>Mark batch done</strong> to save to history.</li>
    </ol>
  </div>
</div>
</main>

<div class="confirm-backdrop" id="confirmModal" role="dialog" aria-modal="true">
  <div class="confirm-box">
    <h3 style="margin:0 0 0.5rem;">Finish this batch?</h3>
    <p style="margin:0 0 1rem;color:var(--muted);font-size:0.9rem;">
      Batch #<?= (int) $batchId ?> with <?= (int) $batch['row_count'] ?> rows will be saved as completed and appear in history.
    </p>
    <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
      <button type="button" class="btn" id="confirmCancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="confirmOk">Yes, mark done</button>
    </div>
  </div>
</div>

<script>
(function () {
  var btn = document.getElementById('btnDone');
  if (!btn) return;
  var modal = document.getElementById('confirmModal');
  document.getElementById('confirmCancel').addEventListener('click', function () {
    modal.classList.remove('open');
  });
  btn.addEventListener('click', function () { modal.classList.add('open'); });
  document.getElementById('confirmOk').addEventListener('click', function () {
    btn.disabled = true;
    fetch('api/complete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ batch_id: <?= (int) $batchId ?> })
    }).then(function (r) { return r.json(); }).then(function (data) {
      if (data.ok) {
        window.location.href = 'batches.php?done=1';
      } else {
        alert(data.error || 'Could not complete batch.');
        btn.disabled = false;
        modal.classList.remove('open');
      }
    }).catch(function () {
      alert('Request failed.');
      btn.disabled = false;
    });
  });
})();
</script>
<?php rts_admin_sidebar_script(); ?>
</body>
</html>
