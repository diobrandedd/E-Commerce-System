<?php
/**
 * api.php — Honey Order Editor API
 * Handles: get_orders, save_edit, export
 */

header('Content-Type: application/json');

$UPLOAD_DIR  = __DIR__ . '/uploads/';
$EDITS_FILE  = __DIR__ . '/uploads/edits.json';
$ORDERS_FILE = $UPLOAD_DIR . 'orders_latest.xlsx';

// Fallback to bundled sample
if (!file_exists($ORDERS_FILE) && file_exists(__DIR__ . '/orders.xlsx')) {
    copy(__DIR__ . '/orders.xlsx', $ORDERS_FILE);
}

$action = $_GET['action'] ?? '';

// ──────────────────────────────────────────────
//  GET ORDERS
// ──────────────────────────────────────────────
if ($action === 'get_orders') {
    if (!file_exists($ORDERS_FILE)) {
        echo json_encode(['error' => 'No orders file uploaded yet.']);
        exit;
    }

    try {
        $orders = readOrdersFromXlsx($ORDERS_FILE);
        // Load any persisted edits
        $edits = [];
        if (file_exists($EDITS_FILE)) {
            $edits = json_decode(file_get_contents($EDITS_FILE), true) ?? [];
        }
        echo json_encode(['orders' => $orders, 'edits' => $edits]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ──────────────────────────────────────────────
//  SAVE EDIT (persist per-session)
// ──────────────────────────────────────────────
if ($action === 'save_edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $idx  = (int)($body['idx'] ?? -1);
    $rem  = $body['remarks'] ?? '';

    $edits = [];
    if (file_exists($EDITS_FILE)) {
        $edits = json_decode(file_get_contents($EDITS_FILE), true) ?? [];
    }
    $edits[$idx] = $rem;
    file_put_contents($EDITS_FILE, json_encode($edits));
    echo json_encode(['ok' => true]);
    exit;
}

// ──────────────────────────────────────────────
//  EXPORT
// ──────────────────────────────────────────────
if ($action === 'export' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($ORDERS_FILE)) {
        echo json_encode(['error' => 'No orders file found.']);
        exit;
    }

    $body  = json_decode(file_get_contents('php://input'), true);
    $edits = $body['edits'] ?? [];

    try {
        $outPath = writeUpdatedXlsx($ORDERS_FILE, $edits);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="orders_updated.xlsx"');
        header('Content-Length: ' . filesize($outPath));
        header('Cache-Control: no-cache');
        ob_clean();
        flush();
        readfile($outPath);
        unlink($outPath);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['error' => 'Unknown action']);
exit;

// ══════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════

/**
 * Read orders from .xlsx using Python (openpyxl).
 * Returns array of {receiver, phone, address, remarks, row_num}
 */
function readOrdersFromXlsx(path) {
    $python = escapeshellarg($path);
    $script = <<<'PY'
import sys, json
import openpyxl

wb = openpyxl.load_workbook(sys.argv[1], data_only=True)
ws = wb.active

# Find header row (row with "Receiver(*)" text)
header_row = None
for row in ws.iter_rows():
    for cell in row:
        v = str(cell.value or '')
        if 'Receiver(*)' in v or 'Receiver(*' in v:
            header_row = cell.row
            break
    if header_row: break

if not header_row:
    print(json.dumps([]))
    sys.exit(0)

orders = []
for row in ws.iter_rows(min_row=header_row+1, values_only=True):
    receiver = str(row[0] or '').strip()
    if not receiver or receiver in ('Receiver(*)', '(*) Information that must be filled out'):
        continue
    phone   = str(row[1] or '').strip()
    address = str(row[2] or '').strip()
    province = str(row[3] or '').strip()
    city    = str(row[4] or '').strip()
    parcel  = str(row[7] or '').strip()
    cod     = str(row[11] or '').strip()
    remarks = str(row[12] or '').strip() if len(row) > 12 else ''
    orders.append({
        'receiver': receiver,
        'phone': phone,
        'address': address,
        'province': province,
        'city': city,
        'parcel': parcel,
        'cod': cod,
        'remarks': remarks,
    })

print(json.dumps(orders, ensure_ascii=False))
PY;

    $tmpScript = tempnam(sys_get_temp_dir(), 'honey_read_') . '.py';
    file_put_contents($tmpScript, $script);

    $cmd = 'python3 ' . $tmpScript . ' ' . $python . ' 2>&1';
    $out = shell_exec($cmd);
    unlink($tmpScript);

    $data = json_decode($out, true);
    if (!is_array($data)) {
        throw new Exception("Failed to parse orders. Python output: " . substr($out, 0, 500));
    }
    return $data;
}

/**
 * Write updated xlsx with cleaned remarks in column M.
 */
function writeUpdatedXlsx(sourcePath, array $edits) {
    $outPath = tempnam(sys_get_temp_dir(), 'honey_out_') . '.xlsx';
    $editsJson = json_encode($edits, JSON_UNESCAPED_UNICODE);

    $script = <<<'PY'
import sys, json
import openpyxl

src, dst, edits_json = sys.argv[1], sys.argv[2], sys.argv[3]
edits = json.loads(edits_json)

wb = openpyxl.load_workbook(src)
ws = wb.active

# Find data start row
header_row = None
for row in ws.iter_rows():
    for cell in row:
        v = str(cell.value or '')
        if 'Receiver(*)' in v or 'Receiver(*' in v:
            header_row = cell.row
            break
    if header_row: break

if not header_row:
    wb.save(dst)
    sys.exit(0)

data_start = header_row + 1
order_idx  = 0
for row_num in range(data_start, ws.max_row + 1):
    cell_a = ws.cell(row=row_num, column=1).value
    receiver = str(cell_a or '').strip()
    if not receiver:
        continue
    key = str(order_idx)
    if key in edits:
        ws.cell(row=row_num, column=13).value = edits[key]
    order_idx += 1

wb.save(dst)
print('OK')
PY;

    $tmpScript = tempnam(sys_get_temp_dir(), 'honey_write_') . '.py';
    file_put_contents($tmpScript, $script);

    $cmd = 'python3 ' . $tmpScript
         . ' ' . escapeshellarg($sourcePath)
         . ' ' . escapeshellarg($outPath)
         . ' ' . escapeshellarg($editsJson)
         . ' 2>&1';

    $out = shell_exec($cmd);
    unlink($tmpScript);

    if (!file_exists($outPath)) {
        throw new Exception("Export failed. Output: " . substr($out, 0, 500));
    }
    return $outPath;
}
