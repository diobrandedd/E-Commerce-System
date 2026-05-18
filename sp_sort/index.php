<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
sp_sort_user();

$error = isset($_GET['error']) ? (string) $_GET['error'] : '';
$pageTitle = 'SP Sort — import & print';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="sp_sort.css">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar_styles.php'; ?>
</head>
<body class="admin-layout">
<?php rts_admin_sidebar('sp_sort', '../', sp_sort_delivery_nav_badge()); ?>
<main>
<div class="wrap">
  <h1 style="margin:0 0 0.75rem;font-size:1.15rem;">SP Sort — import</h1>
  <p style="color:var(--muted);margin:0 0 1rem;">Merge 1–3 Excel files, sort by column D (whole rows move together), print, then use carousel mode while writing waybills.</p>

  <?php if ($error !== ''): ?>
    <div class="msg msg-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" action="process.php" enctype="multipart/form-data" id="uploadForm">
      <p style="margin:0 0 0.5rem;font-weight:600;">Upload Excel files (1–3)</p>
      <div class="drop-grid">
        <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="drop-zone" id="zone<?= $i ?>">
          <input type="file" name="files[]" accept=".xlsx,.xls" id="file<?= $i ?>" onchange="pickFile(<?= $i ?>)">
          <div id="label<?= $i ?>">File <?= $i + 1 ?></div>
        </div>
        <?php endfor; ?>
      </div>

      <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;margin:1rem 0;">
        <label style="font-size:0.9rem;">
          <input type="radio" name="sort_dir" value="asc" checked> Sort column D ascending
        </label>
        <label style="font-size:0.9rem;">
          <input type="radio" name="sort_dir" value="desc"> Descending
        </label>
        <label style="font-size:0.9rem;">
          <input type="checkbox" name="has_header"> First row is header (only for non-Pyxfood sheets)
        </label>
      </div>

      <p style="font-size:0.82rem;color:var(--muted);margin:0 0 1rem;">
        Pyxfood Excel sheets (iPYX header, Order No./Name/Item/s/Courier) are detected automatically.
        Export uses the same layout as your print sheet, including <strong>Prepared by</strong> at the bottom.
        Courier colors: <span style="background:var(--flash);padding:0 0.25rem;">Flash</span>
        <span style="background:var(--spx);padding:0 0.25rem;">SPX</span>
      </p>

      <button type="submit" class="btn btn-primary" id="submitBtn">Merge &amp; sort →</button>
    </form>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.5rem;font-size:1rem;">Workflow</h2>
    <ol style="margin:0;padding-left:1.2rem;color:var(--muted);font-size:0.9rem;">
      <li>Upload and merge files (sorted by column D)</li>
      <li>Download / print merged sheet on A4</li>
      <li>Print waybills separately</li>
      <li>Carousel through each row for waybill entry</li>
      <li>Mark batch done — saved to history with filters</li>
    </ol>
  </div>
</div>
</main>
<?php rts_admin_sidebar_script(); ?>
<script>
function pickFile(i) {
  var input = document.getElementById('file' + i);
  var zone = document.getElementById('zone' + i);
  var label = document.getElementById('label' + i);
  if (input.files && input.files[0]) {
    zone.classList.add('has-file');
    label.textContent = input.files[0].name;
  } else {
    zone.classList.remove('has-file');
    label.textContent = 'File ' + (i + 1);
  }
}
document.getElementById('uploadForm').addEventListener('submit', function () {
  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').textContent = 'Processing…';
});
</script>
</body>
</html>
