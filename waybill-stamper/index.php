<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_gate.php';

// ── Download handler ──────────────────────────────────────────────────────
if (isset($_GET['download'])) {
    $file = __DIR__ . '/outputs/' . basename($_GET['download']);
    $name = $_GET['name'] ?? basename($file);
    if (file_exists($file)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        @unlink($file);
    }
    exit;
}

// ── AJAX process handler ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process') {
    header('Content-Type: application/json');

    $uploadDir = __DIR__ . '/uploads/';
    $outputDir = __DIR__ . '/outputs/';
    foreach ([$uploadDir, $outputDir] as $d) if (!is_dir($d)) mkdir($d, 0755, true);

    $pdfs   = [];
    $excels = [];
    $errors = [];

    // Handle PDFs
    if (!empty($_FILES['pdfs']['name'][0])) {
        foreach ($_FILES['pdfs']['name'] as $i => $name) {
            if ($_FILES['pdfs']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
                $errors[] = "$name is not a PDF"; continue;
            }
            $dest = $uploadDir . uniqid('pdf_') . '.pdf';
            move_uploaded_file($_FILES['pdfs']['tmp_name'][$i], $dest);
            $pdfs[] = $dest;
        }
    }

    // Handle Excels
    if (!empty($_FILES['excels']['name'][0])) {
        foreach ($_FILES['excels']['name'] as $i => $name) {
            if ($_FILES['excels']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'])) {
                $errors[] = "$name is not an Excel file"; continue;
            }
            $dest = $uploadDir . uniqid('xls_') . '.' . $ext;
            move_uploaded_file($_FILES['excels']['tmp_name'][$i], $dest);
            $excels[] = $dest;
        }
    }

    if (empty($pdfs))   $errors[] = 'No PDF waybills uploaded.';
    if (empty($excels)) $errors[] = 'No Excel order sheets uploaded.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $pdfList   = $uploadDir . uniqid('pdf_list_') . '.json';
    $excelList = $uploadDir . uniqid('xls_list_') . '.json';
    $outputFile = $outputDir . 'Stamped_Waybills_' . date('Ymd_His') . '.pdf';
    $logFile    = $uploadDir . uniqid('log_') . '.txt';

    file_put_contents($pdfList,   json_encode($pdfs));
    file_put_contents($excelList, json_encode($excels));

    $script = escapeshellarg(__DIR__ . '/processor.py');
    $cmd = "python3 $script"
         . " --pdfs "   . escapeshellarg($pdfList)
         . " --excels " . escapeshellarg($excelList)
         . " --output " . escapeshellarg($outputFile)
         . " --log "    . escapeshellarg($logFile)
         . " 2>&1";

    $output = shell_exec($cmd);

    // Cleanup uploads
    foreach (array_merge($pdfs, $excels) as $f) @unlink($f);
    @unlink($pdfList);
    @unlink($excelList);

    $log = file_exists($logFile) ? file_get_contents($logFile) : $output;
    @unlink($logFile);

    if (file_exists($outputFile)) {
        $dlName = 'Stamped_Waybills_' . date('Y-m-d') . '.pdf';
        // Parse stats
        preg_match('/STAT_TOTAL:(\d+)/',     $output, $mT);
        preg_match('/STAT_MATCHED:(\d+)/',   $output, $mM);
        preg_match('/STAT_UNMATCHED:(\d+)/', $output, $mU);

        $unmatched = [];
        preg_match_all('/UNMATCHED_TN:(.+)/', $output, $mUN);
        if (!empty($mUN[1])) $unmatched = $mUN[1];

        echo json_encode([
            'success'   => true,
            'file'      => basename($outputFile),
            'filename'  => $dlName,
            'total'     => $mT[1]  ?? '?',
            'matched'   => $mM[1]  ?? '?',
            'unmatched' => $mU[1]  ?? '0',
            'unmatched_list' => $unmatched,
            'log'       => $log,
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'errors'  => ['Processing failed. Check the log below.'],
            'log'     => $output,
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Waybill Stamper — Finest Wellness PH</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@300;400;500&display=swap');

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --bg:      #0b0e15;
  --surf:    #111520;
  --card:    #171c28;
  --border:  #222840;
  --accent:  #3ecf8e;
  --accent2: #1ea672;
  --warn:    #f59e0b;
  --danger:  #ef4444;
  --text:    #e2e8f5;
  --muted:   #64748b;
  --r:       14px;
}

body{
  font-family:'JetBrains Mono',monospace;
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
  display:flex;
  flex-direction:column;
  align-items:center;
  padding:48px 20px 100px;
}

/* Animated dot-grid bg */
body::before{
  content:'';
  position:fixed;inset:0;
  background-image:radial-gradient(rgba(62,207,142,.08) 1px,transparent 1px);
  background-size:28px 28px;
  pointer-events:none;z-index:0;
  animation:bgshift 20s linear infinite;
}
@keyframes bgshift{0%{background-position:0 0}100%{background-position:28px 28px}}

.wrap{position:relative;z-index:1;width:100%;max-width:820px}

/* ── Header ── */
header{text-align:center;margin-bottom:52px}
.pill{
  display:inline-flex;align-items:center;gap:8px;
  font-size:11px;letter-spacing:3px;text-transform:uppercase;
  color:var(--accent);border:1px solid rgba(62,207,142,.3);
  padding:5px 16px;border-radius:99px;margin-bottom:20px;
}
.pill-dot{width:6px;height:6px;background:var(--accent);border-radius:50%;
  animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}

h1{
  font-family:'Syne',sans-serif;
  font-size:clamp(30px,5.5vw,50px);
  font-weight:800;letter-spacing:-2px;line-height:1;
}
h1 em{font-style:normal;color:var(--accent)}
.sub{color:var(--muted);font-size:12px;margin-top:12px;line-height:1.7}

/* ── Upload grid ── */
.upload-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
@media(max-width:540px){.upload-grid{grid-template-columns:1fr}}

.zone{
  background:var(--card);border:2px dashed var(--border);
  border-radius:var(--r);padding:28px 16px;text-align:center;
  cursor:pointer;position:relative;
  transition:border-color .25s,background .25s,transform .15s;
}
.zone:hover,.zone.over{
  border-color:var(--accent);
  background:rgba(62,207,142,.05);
  transform:translateY(-2px);
}
.zone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.zone-icon{font-size:32px;margin-bottom:10px;display:block}
.zone-label{
  font-family:'Syne',sans-serif;font-weight:700;font-size:14px;margin-bottom:5px
}
.zone-hint{font-size:10px;color:var(--muted)}
.zone-tag{
  display:inline-block;margin-top:8px;
  background:rgba(62,207,142,.12);color:var(--accent);
  font-size:9px;letter-spacing:1.5px;padding:3px 10px;border-radius:99px;
}

.file-list{margin-top:10px;text-align:left;max-height:90px;overflow-y:auto}
.file-item{font-size:10px;color:var(--accent);padding:2px 0;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.file-item::before{content:'✓ '}

/* ── Process button ── */
.btn-go{
  width:100%;padding:16px;margin-bottom:20px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  border:none;border-radius:var(--r);
  font-family:'Syne',sans-serif;font-weight:800;
  font-size:15px;letter-spacing:.5px;color:#fff;
  cursor:pointer;position:relative;overflow:hidden;
  transition:opacity .2s,transform .1s,box-shadow .2s;
  box-shadow:0 0 30px rgba(62,207,142,.2);
}
.btn-go::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent);
  transform:translateX(-100%);transition:transform .5s;
}
.btn-go:hover::after{transform:translateX(100%)}
.btn-go:hover{opacity:.92;box-shadow:0 0 40px rgba(62,207,142,.35)}
.btn-go:active{transform:scale(.99)}
.btn-go:disabled{opacity:.3;cursor:not-allowed;box-shadow:none}
.btn-go:disabled::after{display:none}

/* ── Progress ── */
#prog-wrap{display:none;margin-bottom:20px}
.prog-track{height:3px;background:var(--border);border-radius:99px;overflow:hidden;margin-bottom:10px}
.prog-fill{
  height:100%;width:0%;border-radius:99px;
  background:linear-gradient(90deg,var(--accent),#60f9b8);
  transition:width .5s ease;
}
.prog-steps{display:flex;gap:0;margin-bottom:8px}
.step-dot{
  flex:1;height:3px;background:var(--border);border-radius:99px;
  margin:0 2px;transition:background .4s;
}
.step-dot.done{background:var(--accent)}
.step-dot.active{background:var(--warn);animation:blink .8s ease infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
.prog-label{font-size:11px;color:var(--muted);text-align:center}

/* ── Results ── */
#result{display:none}
.result-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--r);padding:28px;
}
.result-head{
  font-family:'Syne',sans-serif;font-weight:800;
  font-size:20px;margin-bottom:20px;display:flex;align-items:center;gap:10px;
}
.result-head.ok{color:var(--accent)}
.result-head.fail{color:var(--danger)}

.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:22px}
.stat{
  background:var(--surf);border-radius:10px;
  padding:16px;text-align:center;
  border:1px solid var(--border);
}
.stat-n{
  font-family:'Syne',sans-serif;font-weight:800;
  font-size:34px;color:var(--accent);
  animation:countup .6s ease forwards;
}
.stat-n.warn{color:var(--warn)}
.stat-n.danger{color:var(--danger)}
.stat-l{font-size:9px;color:var(--muted);text-transform:uppercase;letter-spacing:1.5px;margin-top:4px}

.btn-dl{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:14px;margin-bottom:12px;
  background:var(--accent);border:none;border-radius:var(--r);
  font-family:'Syne',sans-serif;font-weight:700;font-size:14px;
  color:#0b1a12;cursor:pointer;text-decoration:none;
  transition:opacity .2s,transform .1s;
}
.btn-dl:hover{opacity:.88;transform:translateY(-1px)}

.btn-reset{
  width:100%;padding:10px;background:transparent;
  border:1px solid var(--border);border-radius:var(--r);
  color:var(--muted);font-family:'JetBrains Mono',monospace;
  font-size:12px;cursor:pointer;transition:border-color .2s,color .2s;
}
.btn-reset:hover{border-color:var(--text);color:var(--text)}

/* Unmatched list */
.um-section{margin-top:16px}
.um-head{
  font-size:10px;color:var(--warn);text-transform:uppercase;
  letter-spacing:2px;margin-bottom:8px;
}
.um-item{
  font-size:11px;color:var(--warn);padding:3px 0;
  border-bottom:1px solid rgba(245,158,11,.1);
}
.um-item::before{content:'⚠ '}

/* Log box */
.log-toggle{
  font-size:11px;color:var(--muted);cursor:pointer;
  margin-top:12px;text-decoration:underline;text-underline-offset:3px;
}
.log-box{
  display:none;background:var(--surf);border:1px solid var(--border);
  border-radius:8px;padding:12px;margin-top:8px;
  font-size:10px;color:var(--muted);
  white-space:pre-wrap;max-height:200px;overflow-y:auto;
}

/* Error list */
.err-list{list-style:none}
.err-list li{color:var(--danger);font-size:13px;padding:5px 0}
.err-list li::before{content:'✕ '}

/* Platform legend */
.legend{
  display:flex;gap:12px;flex-wrap:wrap;
  font-size:10px;color:var(--muted);
  margin-bottom:20px;justify-content:center;
}
.legend-item{display:flex;align-items:center;gap:5px}
.legend-dot{width:8px;height:8px;border-radius:50%}
</style>
</head>
<body>
<div class="wrap">

<header>
  <div class="pill"><span class="pill-dot"></span> Finest Wellness PH</div>
  <h1>Waybill <em>Stamper</em></h1>
  <p class="sub">Upload PDF waybills + iPYX Excel order sheets.<br>
  The system scans each waybill, matches it to your order, and stamps the item/s directly onto the PDF.</p>
</header>

<div class="legend">
  <div class="legend-item"><div class="legend-dot" style="background:#3ecf8e"></div> TikTok/J&T — text layer</div>
  <div class="legend-item"><div class="legend-dot" style="background:#60a5fa"></div> Lazada/Flash — OCR fallback</div>
  <div class="legend-item"><div class="legend-dot" style="background:#a78bfa"></div> Shopee Express — OCR fallback</div>
</div>

<form id="form" enctype="multipart/form-data">
  <div class="upload-grid">

    <div class="zone" id="pdf-zone">
      <input type="file" name="pdfs[]" id="pdf-in" accept=".pdf" multiple>
      <span class="zone-icon">📄</span>
      <div class="zone-label">PDF Waybills</div>
      <div class="zone-hint">Drop files or click to browse</div>
      <div class="zone-tag">TikTok · Lazada · Shopee</div>
      <div class="file-list" id="pdf-list"></div>
    </div>

    <div class="zone" id="xls-zone">
      <input type="file" name="excels[]" id="xls-in" accept=".xlsx,.xls" multiple>
      <span class="zone-icon">📊</span>
      <div class="zone-label">iPYX Order Sheets</div>
      <div class="zone-hint">Drop files or click to browse</div>
      <div class="zone-tag">.xlsx · .xls · All sheets</div>
      <div class="file-list" id="xls-list"></div>
    </div>

  </div>

  <button type="submit" class="btn-go" id="btn-go" disabled>
    ⚡ Stamp Waybills
  </button>
</form>

<!-- Progress -->
<div id="prog-wrap">
  <div class="prog-steps" id="prog-steps">
    <div class="step-dot" id="s0"></div>
    <div class="step-dot" id="s1"></div>
    <div class="step-dot" id="s2"></div>
    <div class="step-dot" id="s3"></div>
    <div class="step-dot" id="s4"></div>
    <div class="step-dot" id="s5"></div>
  </div>
  <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
  <div class="prog-label" id="prog-label">Starting…</div>
</div>

<!-- Result -->
<div id="result">
  <div class="result-card" id="result-card"></div>
</div>

</div><!-- .wrap -->

<script>
const pdfIn    = document.getElementById('pdf-in');
const xlsIn    = document.getElementById('xls-in');
const pdfList  = document.getElementById('pdf-list');
const xlsList  = document.getElementById('xls-list');
const btnGo    = document.getElementById('btn-go');
const form     = document.getElementById('form');
const progWrap = document.getElementById('prog-wrap');
const progFill = document.getElementById('prog-fill');
const progLbl  = document.getElementById('prog-label');
const result   = document.getElementById('result');
const rCard    = document.getElementById('result-card');

let hasPdf = false, hasXls = false;

function renderList(input, listEl) {
  listEl.innerHTML = '';
  [...input.files].forEach(f => {
    const d = document.createElement('div');
    d.className = 'file-item';
    d.textContent = f.name;
    listEl.appendChild(d);
  });
  return input.files.length > 0;
}

pdfIn.addEventListener('change', () => { hasPdf = renderList(pdfIn, pdfList); check(); });
xlsIn.addEventListener('change', () => { hasXls = renderList(xlsIn, xlsList); check(); });
function check() { btnGo.disabled = !(hasPdf && hasXls); }

// Drag styling
['pdf-zone','xls-zone'].forEach(id => {
  const el = document.getElementById(id);
  el.addEventListener('dragover',  e => { e.preventDefault(); el.classList.add('over'); });
  el.addEventListener('dragleave', () => el.classList.remove('over'));
  el.addEventListener('drop',      () => el.classList.remove('over'));
});

// Progress steps config
const STEPS = [
  [8,  'Reading Excel order sheets…'],
  [20, 'Scanning PDF text layers…'],
  [38, 'Running OCR on image-only pages…'],
  [58, 'Matching tracking numbers to orders…'],
  [78, 'Stamping item labels onto waybills…'],
  [94, 'Finalizing output PDF…'],
];

let stepIdx = 0, progTimer;

function startProgress() {
  stepIdx = 0; progWrap.style.display = 'block';
  result.style.display = 'none';
  progFill.style.width = '2%';
  progTimer = setInterval(() => {
    if (stepIdx < STEPS.length) {
      const [pct, txt] = STEPS[stepIdx];
      // Mark dots
      for (let i = 0; i < 6; i++) {
        const d = document.getElementById(`s${i}`);
        if (i < stepIdx)      d.className = 'step-dot done';
        else if (i === stepIdx) d.className = 'step-dot active';
        else                  d.className = 'step-dot';
      }
      progFill.style.width = pct + '%';
      progLbl.textContent  = txt;
      stepIdx++;
    }
  }, 700);
}

function stopProgress() {
  clearInterval(progTimer);
  for (let i = 0; i < 6; i++) document.getElementById(`s${i}`).className = 'step-dot done';
  progFill.style.width = '100%';
  progLbl.textContent  = 'Complete!';
  setTimeout(() => { progWrap.style.display = 'none'; }, 800);
}

form.addEventListener('submit', async e => {
  e.preventDefault();
  btnGo.disabled = true;
  startProgress();

  const fd = new FormData(form);
  fd.append('action', 'process');

  try {
    const res  = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();
    stopProgress();
    showResult(data);
  } catch(err) {
    stopProgress();
    showResult({ success: false, errors: ['Server error: ' + err.message] });
  }
});

function showResult(data) {
  result.style.display = 'block';
  rCard.innerHTML = '';

  if (!data.success) {
    rCard.innerHTML = `
      <div class="result-head fail">✕ Processing Failed</div>
      <ul class="err-list">${(data.errors||[]).map(e=>`<li>${esc(e)}</li>`).join('')}</ul>
      ${data.log ? `<div class="log-toggle" onclick="toggleLog(this)">▸ Show debug log</div>
      <div class="log-box">${esc(data.log)}</div>` : ''}
      <br><button class="btn-reset" onclick="reset()">← Try Again</button>
    `;
    return;
  }

  const unmatchedHTML = data.unmatched_list?.length
    ? `<div class="um-section">
        <div class="um-head">⚠ Unmatched Waybills (${data.unmatched_list.length})</div>
        ${data.unmatched_list.map(u=>`<div class="um-item">${esc(u)}</div>`).join('')}
       </div>` : '';

  rCard.innerHTML = `
    <div class="result-head ok">✓ Waybills Stamped</div>
    <div class="stats">
      <div class="stat">
        <div class="stat-n">${data.total}</div>
        <div class="stat-l">Pages Processed</div>
      </div>
      <div class="stat">
        <div class="stat-n">${data.matched}</div>
        <div class="stat-l">Stamped ✓</div>
      </div>
      <div class="stat">
        <div class="stat-n ${parseInt(data.unmatched)>0?'danger':''}">
          ${data.unmatched}
        </div>
        <div class="stat-l">Unmatched</div>
      </div>
    </div>
    <a class="btn-dl"
       href="?download=${encodeURIComponent(data.file)}&name=${encodeURIComponent(data.filename)}">
      ⬇ &nbsp;Download Stamped PDF — ${esc(data.filename)}
    </a>
    ${unmatchedHTML}
    <div class="log-toggle" onclick="toggleLog(this)">▸ Show processing log</div>
    <div class="log-box">${esc(data.log||'')}</div>
    <br>
    <button class="btn-reset" onclick="reset()">← Process Another Batch</button>
  `;
}

function toggleLog(el) {
  const box = el.nextElementSibling;
  const open = box.style.display === 'block';
  box.style.display = open ? 'none' : 'block';
  el.textContent = (open ? '▸' : '▾') + ' ' + (open ? 'Show' : 'Hide') + ' processing log';
}

function reset() {
  form.reset();
  pdfList.innerHTML = xlsList.innerHTML = '';
  hasPdf = hasXls = false;
  btnGo.disabled = true;
  result.style.display = 'none';
}

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
