<?php
// ── Download handler ──────────────────────────────────────────────────────────
if (isset($_GET['download'])) {
    $file = __DIR__ . '/outputs/' . basename($_GET['download']);
    $name = $_GET['name'] ?? basename($file);
    if (file_exists($file)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        @unlink($file);
    }
    exit;
}

// ── AJAX process handler ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process') {
    header('Content-Type: application/json');

    $uploadDir = __DIR__ . '/uploads/';
    $outputDir = __DIR__ . '/outputs/';
    foreach ([$uploadDir, $outputDir] as $d) if (!is_dir($d)) mkdir($d, 0755, true);

    $pdfs   = [];
    $errors = [];

    if (!empty($_FILES['pdfs']['name'][0])) {
        foreach ($_FILES['pdfs']['name'] as $i => $name) {
            if ($_FILES['pdfs']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
                $errors[] = "$name is not a PDF"; continue;
            }
            // Keep original name for output Excel naming
            $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', pathinfo($name, PATHINFO_FILENAME));
            $dest = $uploadDir . $safeName . '_' . uniqid() . '.pdf';
            move_uploaded_file($_FILES['pdfs']['tmp_name'][$i], $dest);
            $pdfs[] = $dest;
        }
    }

    if (empty($pdfs)) $errors[] = 'No PDF files uploaded.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $pdfList = $uploadDir . uniqid('pdf_list_') . '.json';
    file_put_contents($pdfList, json_encode($pdfs));

    $script = escapeshellarg(__DIR__ . '/extractor.py');
    $cmd = "python3 $script"
         . " --pdfs "   . escapeshellarg($pdfList)
         . " --outdir " . escapeshellarg($outputDir)
         . " 2>&1";

    $output = shell_exec($cmd);

    // Cleanup uploads
    foreach ($pdfs as $f) @unlink($f);
    @unlink($pdfList);

    // Parse OUTPUT_JSON from script stdout
    $results = [];
    if (preg_match('/OUTPUT_JSON:(.+)$/m', $output, $m)) {
        $results = json_decode($m[1], true) ?? [];
    }

    if (!empty($results)) {
        // Build download links for each Excel
        $files = [];
        foreach ($results as $r) {
            $excelPath = $outputDir . $r['excel'];
            if (file_exists($excelPath)) {
                $files[] = [
                    'pdf'      => $r['pdf'],
                    'excel'    => $r['excel'],
                    'total'    => $r['total'],
                    'complete' => $r['complete'],
                    'missing'  => $r['missing'],
                    'dl_url'   => '?download=' . urlencode($r['excel'])
                               . '&name='     . urlencode($r['excel']),
                ];
            }
        }
        echo json_encode(['success' => true, 'files' => $files, 'log' => $output]);
    } else {
        echo json_encode([
            'success' => false,
            'errors'  => ['Extraction failed — no output generated.'],
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
<title>PDF → Excel Extractor — Finest Wellness PH</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@300;400;500&display=swap');

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --bg:     #0b0e15;
  --surf:   #111520;
  --card:   #171c28;
  --border: #222840;
  --accent: #7c6af7;
  --accent2:#5b4de0;
  --green:  #2ecc71;
  --warn:   #f59e0b;
  --danger: #ef4444;
  --text:   #e2e8f5;
  --muted:  #64748b;
  --r:      14px;
}

body{
  font-family:'JetBrains Mono',monospace;
  background:var(--bg);color:var(--text);
  min-height:100vh;display:flex;flex-direction:column;
  align-items:center;padding:48px 20px 100px;
}
body::before{
  content:'';position:fixed;inset:0;
  background-image:radial-gradient(rgba(124,106,247,.07) 1px,transparent 1px);
  background-size:28px 28px;pointer-events:none;z-index:0;
}
.wrap{position:relative;z-index:1;width:100%;max-width:780px}

/* Header */
header{text-align:center;margin-bottom:48px}
.pill{
  display:inline-flex;align-items:center;gap:8px;
  font-size:11px;letter-spacing:3px;text-transform:uppercase;
  color:var(--accent);border:1px solid rgba(124,106,247,.35);
  padding:5px 16px;border-radius:99px;margin-bottom:18px;
}
.pill-dot{width:6px;height:6px;background:var(--accent);border-radius:50%;
  animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}
h1{
  font-family:'Syne',sans-serif;font-size:clamp(28px,5vw,46px);
  font-weight:800;letter-spacing:-2px;line-height:1.05;
}
h1 em{font-style:normal;color:var(--accent)}
.sub{color:var(--muted);font-size:12px;margin-top:12px;line-height:1.7}

/* Info cards */
.info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:28px}
.info-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:10px;padding:14px;text-align:center;
}
.info-icon{font-size:20px;margin-bottom:6px}
.info-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:1.5px}
.info-val{font-size:12px;color:var(--text);margin-top:3px}

/* Drop zone */
.zone{
  background:var(--card);border:2px dashed var(--border);
  border-radius:var(--r);padding:40px 20px;text-align:center;
  cursor:pointer;position:relative;
  transition:border-color .25s,background .25s,transform .15s;
  margin-bottom:14px;
}
.zone:hover,.zone.over{
  border-color:var(--accent);
  background:rgba(124,106,247,.06);
  transform:translateY(-2px);
}
.zone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.zone-icon{font-size:40px;margin-bottom:12px;display:block}
.zone-label{
  font-family:'Syne',sans-serif;font-weight:700;
  font-size:16px;margin-bottom:6px;
}
.zone-hint{font-size:11px;color:var(--muted)}
.zone-tag{
  display:inline-block;margin-top:10px;
  background:rgba(124,106,247,.15);color:var(--accent);
  font-size:9px;letter-spacing:1.5px;padding:3px 12px;border-radius:99px;
}
.file-list{
  margin-top:14px;text-align:left;
  max-height:120px;overflow-y:auto;
}
.file-item{
  font-size:11px;color:var(--accent);padding:3px 0;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  border-bottom:1px solid rgba(124,106,247,.1);
}
.file-item::before{content:'📄  '}
.file-count{
  font-size:11px;color:var(--muted);
  margin-top:6px;text-align:right;
}

/* Button */
.btn-go{
  width:100%;padding:16px;margin-bottom:20px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  border:none;border-radius:var(--r);
  font-family:'Syne',sans-serif;font-weight:800;
  font-size:15px;letter-spacing:.5px;color:#fff;
  cursor:pointer;overflow:hidden;position:relative;
  transition:opacity .2s,transform .1s,box-shadow .2s;
  box-shadow:0 0 30px rgba(124,106,247,.25);
}
.btn-go::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent);
  transform:translateX(-100%);transition:transform .5s;
}
.btn-go:hover::after{transform:translateX(100%)}
.btn-go:hover{opacity:.9;box-shadow:0 0 40px rgba(124,106,247,.4)}
.btn-go:active{transform:scale(.99)}
.btn-go:disabled{opacity:.3;cursor:not-allowed;box-shadow:none}

/* Progress */
#prog-wrap{display:none;margin-bottom:20px}
.prog-track{height:3px;background:var(--border);border-radius:99px;overflow:hidden;margin-bottom:10px}
.prog-fill{
  height:100%;width:0%;border-radius:99px;
  background:linear-gradient(90deg,var(--accent),#a89cf8);
  transition:width .5s ease;
}
.prog-steps{display:flex;gap:2px;margin-bottom:8px}
.step-dot{
  flex:1;height:3px;background:var(--border);
  border-radius:99px;transition:background .4s;
}
.step-dot.done{background:var(--accent)}
.step-dot.active{background:var(--warn);animation:blink .8s ease infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
.prog-label{font-size:11px;color:var(--muted);text-align:center}

/* Results */
#result{display:none}
.result-card{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--r);padding:28px;
}
.result-head{
  font-family:'Syne',sans-serif;font-weight:800;
  font-size:20px;margin-bottom:20px;
  display:flex;align-items:center;gap:10px;
}
.result-head.ok{color:var(--accent)}
.result-head.fail{color:var(--danger)}

/* File result cards */
.file-results{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
.file-result{
  background:var(--surf);border:1px solid var(--border);
  border-radius:10px;padding:16px;
}
.fr-header{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:10px;flex-wrap:wrap;gap:8px;
}
.fr-name{
  font-family:'Syne',sans-serif;font-weight:700;
  font-size:13px;color:var(--text);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  max-width:60%;
}
.fr-stats{display:flex;gap:8px;flex-wrap:wrap}
.badge{
  font-size:10px;padding:3px 10px;border-radius:99px;
  letter-spacing:.5px;font-weight:600;
}
.badge-ok{background:rgba(46,204,113,.15);color:var(--green)}
.badge-warn{background:rgba(245,158,11,.15);color:var(--warn)}
.badge-muted{background:rgba(100,116,139,.15);color:var(--muted)}

.btn-dl{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:11px;
  background:var(--accent);border:none;border-radius:8px;
  font-family:'Syne',sans-serif;font-weight:700;font-size:13px;
  color:#fff;cursor:pointer;text-decoration:none;
  transition:opacity .2s;
}
.btn-dl:hover{opacity:.85}

/* Misc */
.btn-reset{
  width:100%;padding:10px;background:transparent;
  border:1px solid var(--border);border-radius:var(--r);
  color:var(--muted);font-family:'JetBrains Mono',monospace;
  font-size:12px;cursor:pointer;transition:border-color .2s,color .2s;
}
.btn-reset:hover{border-color:var(--text);color:var(--text)}
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
.err-list{list-style:none}
.err-list li{color:var(--danger);font-size:13px;padding:5px 0}
.err-list li::before{content:'✕  '}
</style>
</head>
<body>
<div class="wrap">

<header>
  <div class="pill"><span class="pill-dot"></span>Finest Wellness PH</div>
  <h1>PDF → <em>Excel</em> Extractor</h1>
  <p class="sub">Upload Shopee waybill PDFs — no Excel from the worker?<br>
  The system reads each page and builds a ready-to-use order Excel automatically.</p>
</header>

<!-- Info cards -->
<div class="info-grid">
  <div class="info-card">
    <div class="info-icon">📋</div>
    <div class="info-label">Output Format</div>
    <div class="info-val">iPYX Excel<br>Order No · Name · Item/s · Courier · TN</div>
  </div>
  <div class="info-card">
    <div class="info-icon">🔍</div>
    <div class="info-label">Extraction</div>
    <div class="info-val">Text layer first<br>OCR fallback for image pages</div>
  </div>
  <div class="info-card">
    <div class="info-icon">📦</div>
    <div class="info-label">Supported</div>
    <div class="info-val">Shopee Express<br>SPEPH · SPX · JT</div>
  </div>
</div>

<form id="form" enctype="multipart/form-data">
  <div class="zone" id="pdf-zone">
    <input type="file" name="pdfs[]" id="pdf-in" accept=".pdf" multiple>
    <span class="zone-icon">📄</span>
    <div class="zone-label">Drop PDF Waybills Here</div>
    <div class="zone-hint">Or click to browse — multiple files supported</div>
    <div class="zone-tag">One Excel output per PDF</div>
    <div class="file-list" id="pdf-list"></div>
    <div class="file-count" id="file-count"></div>
  </div>

  <button type="submit" class="btn-go" id="btn-go" disabled>
    ⚡ Extract to Excel
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
  </div>
  <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
  <div class="prog-label" id="prog-label">Starting…</div>
</div>

<!-- Result -->
<div id="result">
  <div class="result-card" id="result-card"></div>
</div>

</div>

<script>
const pdfIn    = document.getElementById('pdf-in');
const pdfList  = document.getElementById('pdf-list');
const fileCount= document.getElementById('file-count');
const btnGo    = document.getElementById('btn-go');
const form     = document.getElementById('form');
const progWrap = document.getElementById('prog-wrap');
const progFill = document.getElementById('prog-fill');
const progLbl  = document.getElementById('prog-label');
const result   = document.getElementById('result');
const rCard    = document.getElementById('result-card');

pdfIn.addEventListener('change', () => {
  const files = [...pdfIn.files];
  pdfList.innerHTML = '';
  files.forEach(f => {
    const d = document.createElement('div');
    d.className = 'file-item';
    d.textContent = f.name;
    pdfList.appendChild(d);
  });
  fileCount.textContent = files.length ? `${files.length} file${files.length>1?'s':''} selected` : '';
  btnGo.disabled = files.length === 0;
});

// Drag styling
const zone = document.getElementById('pdf-zone');
zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('over'));
zone.addEventListener('drop',      () => zone.classList.remove('over'));

const STEPS = [
  [10, 'Reading PDF files…'],
  [30, 'Extracting tracking numbers…'],
  [55, 'Extracting names & items…'],
  [80, 'Building Excel files…'],
  [95, 'Finalizing output…'],
];
let stepIdx = 0, progTimer;

function startProgress() {
  stepIdx = 0; progWrap.style.display = 'block';
  result.style.display = 'none';
  progFill.style.width = '2%';
  progTimer = setInterval(() => {
    if (stepIdx < STEPS.length) {
      const [pct, txt] = STEPS[stepIdx];
      for (let i = 0; i < 5; i++) {
        const d = document.getElementById(`s${i}`);
        d.className = i < stepIdx ? 'step-dot done'
                    : i === stepIdx ? 'step-dot active'
                    : 'step-dot';
      }
      progFill.style.width = pct + '%';
      progLbl.textContent  = txt;
      stepIdx++;
    }
  }, 900);
}

function stopProgress() {
  clearInterval(progTimer);
  for (let i = 0; i < 5; i++) document.getElementById(`s${i}`).className = 'step-dot done';
  progFill.style.width = '100%';
  progLbl.textContent  = 'Complete!';
  setTimeout(() => { progWrap.style.display = 'none'; }, 700);
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
      <div class="result-head fail">✕ Extraction Failed</div>
      <ul class="err-list">${(data.errors||[]).map(e=>`<li>${esc(e)}</li>`).join('')}</ul>
      ${data.log ? `<div class="log-toggle" onclick="toggleLog(this)">▸ Show debug log</div>
      <div class="log-box">${esc(data.log)}</div>` : ''}
      <br><button class="btn-reset" onclick="reset()">← Try Again</button>
    `;
    return;
  }

  const files = data.files || [];
  const totalPages    = files.reduce((s,f) => s + f.total, 0);
  const totalComplete = files.reduce((s,f) => s + f.complete, 0);
  const totalMissing  = files.reduce((s,f) => s + f.missing, 0);

  const fileCards = files.map(f => `
    <div class="file-result">
      <div class="fr-header">
        <div class="fr-name">📄 ${esc(f.pdf)}</div>
        <div class="fr-stats">
          <span class="badge badge-muted">${f.total} pages</span>
          <span class="badge badge-ok">${f.complete} complete</span>
          ${f.missing > 0 ? `<span class="badge badge-warn">${f.missing} incomplete</span>` : ''}
        </div>
      </div>
      <a class="btn-dl" href="${esc(f.dl_url)}">
        ⬇ &nbsp;Download — ${esc(f.excel)}
      </a>
    </div>
  `).join('');

  rCard.innerHTML = `
    <div class="result-head ok">✓ Extraction Complete</div>
    <div class="file-results">${fileCards}</div>
    <div class="log-toggle" onclick="toggleLog(this)">▸ Show processing log</div>
    <div class="log-box">${esc(data.log||'')}</div>
    <br>
    <button class="btn-reset" onclick="reset()">← Extract Another Batch</button>
  `;
}

function toggleLog(el) {
  const box  = el.nextElementSibling;
  const open = box.style.display === 'block';
  box.style.display = open ? 'none' : 'block';
  el.textContent = (open ? '▸ Show' : '▾ Hide') + ' processing log';
}

function reset() {
  form.reset();
  pdfList.innerHTML = '';
  fileCount.textContent = '';
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
