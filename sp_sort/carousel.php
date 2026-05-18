<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

sp_sort_user();
$pdo = rts_pdo();
$batchId = isset($_GET['batch']) ? (int) $_GET['batch'] : 0;
if ($batchId < 1) {
    header('Location: index.php', true, 302);
    exit;
}
$batch = sp_sort_batch_or_404($pdo, $batchId);

$st = $pdo->prepare(
    'SELECT row_num, sort_key_d, courier, shop, channel_text, row_data
     FROM sp_sort_rows WHERE batch_id = ? ORDER BY row_num ASC'
);
$st->execute([$batchId]);
$dbRows = $st->fetchAll(PDO::FETCH_ASSOC);

$slides = [];
foreach ($dbRows as $r) {
    $cells = json_decode((string) $r['row_data'], true);
    if (!is_array($cells)) {
        $cells = [];
    }
    $fields = [];
    foreach ($cells as $ci => $cell) {
        $letter = chr(65 + (int) $ci);
        $fields[] = ['col' => $letter, 'value' => (string) $cell];
    }
    $courier = (string) $r['courier'];
    $slides[] = [
        'num' => (int) $r['row_num'],
        'total' => count($dbRows),
        'sort_d' => (string) $r['sort_key_d'],
        'courier' => $courier,
        'shop' => (string) $r['shop'],
        'channel' => (string) ($r['channel_text'] ?? ''),
        'fields' => $fields,
    ];
}
$slidesJson = json_encode($slides, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carousel — batch #<?= (int) $batchId ?></title>
  <link rel="stylesheet" href="sp_sort.css">
  <style>
    .carousel-wrap { max-width: 720px; margin: 0 auto; }
    .slide-card {
      background: var(--panel);
      border: 2px solid var(--border);
      border-radius: 14px;
      padding: 1.25rem;
      min-height: 280px;
    }
    .slide-meta { font-size: 0.85rem; color: var(--muted); margin-bottom: 0.75rem; }
    .field { display: grid; grid-template-columns: 2.5rem 1fr; gap: 0.35rem 0.75rem; padding: 0.35rem 0; border-bottom: 1px solid #f0e6d4; font-size: 0.92rem; }
    .field .col { font-weight: 700; color: var(--muted); }
    .channel-badge {
      display: inline-block;
      padding: 0.25rem 0.6rem;
      border-radius: 8px;
      font-weight: 600;
      margin-bottom: 0.75rem;
    }
    .channel-badge.flash { background: var(--flash); }
    .channel-badge.spx { background: var(--spx); }
    .nav-row { display: flex; align-items: center; justify-content: center; gap: 1rem; margin: 1.25rem 0; }
    .nav-btn {
      width: 52px; height: 52px; border-radius: 50%;
      border: 2px solid var(--border);
      background: #fff7ea;
      font-size: 1.4rem;
      cursor: pointer;
    }
    .nav-btn:disabled { opacity: 0.35; cursor: not-allowed; }
    .progress { text-align: center; font-size: 0.9rem; color: var(--muted); }
  </style>
</head>
<body>
<div class="wrap carousel-wrap">
  <div class="topbar">
    <a href="workflow.php?batch=<?= (int) $batchId ?>">← Batch</a>
    <h1>Carousel</h1>
    <span class="progress" id="progressLabel">—</span>
  </div>

  <div class="slide-card" id="slideCard">
    <p style="margin:0;color:var(--muted);">Loading…</p>
  </div>

  <div class="nav-row">
    <button type="button" class="nav-btn" id="btnPrev" aria-label="Previous">‹</button>
    <button type="button" class="nav-btn" id="btnNext" aria-label="Next">›</button>
  </div>

  <p style="text-align:center;margin-top:1rem;">
    <a class="btn" href="workflow.php?batch=<?= (int) $batchId ?>">Back to batch workflow</a>
  </p>
</div>
<script>
var slides = <?= $slidesJson ?: '[]' ?>;
var idx = 0;

function courierLabel(c) {
  if (c === 'flash') return 'Flash Express';
  if (c === 'spx') return 'SPX Express';
  if (c === 'jnt') return 'J&T Express';
  return c || '—';
}

function render() {
  if (!slides.length) {
    document.getElementById('slideCard').innerHTML = '<p>No rows in this batch.</p>';
    return;
  }
  var s = slides[idx];
  document.getElementById('progressLabel').textContent = 'Row ' + s.num + ' of ' + s.total;
  var html = '<div class="slide-meta">Column D: <strong>' + escapeHtml(s.sort_d) + '</strong> · ' + escapeHtml(s.shop) + ' · ' + escapeHtml(courierLabel(s.courier)) + '</div>';
  if (s.channel) {
    var badgeClass = s.courier === 'flash' ? 'flash' : (s.courier === 'spx' ? 'spx' : '');
    html += '<div class="channel-badge ' + badgeClass + '">' + escapeHtml(s.channel) + '</div>';
  }
  s.fields.forEach(function (f) {
    if (String(f.value).trim() === '') return;
    html += '<div class="field"><span class="col">' + escapeHtml(f.col) + '</span><span>' + escapeHtml(f.value) + '</span></div>';
  });
  document.getElementById('slideCard').innerHTML = html;
  document.getElementById('btnPrev').disabled = idx <= 0;
  document.getElementById('btnNext').disabled = idx >= slides.length - 1;
}

function escapeHtml(t) {
  var d = document.createElement('div');
  d.textContent = t;
  return d.innerHTML;
}

document.getElementById('btnPrev').addEventListener('click', function () {
  if (idx > 0) { idx--; render(); }
});
document.getElementById('btnNext').addEventListener('click', function () {
  if (idx < slides.length - 1) { idx++; render(); }
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'ArrowLeft') document.getElementById('btnPrev').click();
  if (e.key === 'ArrowRight') document.getElementById('btnNext').click();
});
render();
</script>
</body>
</html>
