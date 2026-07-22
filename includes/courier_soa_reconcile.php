<?php

declare(strict_types=1);

/**
 * Courier SOA (J&T / SPX) reconciliation vs shipped Online Parcels by tracking.
 */

require_once __DIR__ . '/online_order_parcels.php';
require_once __DIR__ . '/courier_detect.php';
require_once __DIR__ . '/waybill_stamper.php';
require_once __DIR__ . '/rts_storage.php';

function courier_soa_storage_root(): string
{
    return rts_storage_csr_tool_root(
        RTS_STORAGE_BUCKET_COURIER_SOA,
        'courier-soa',
        ['stash', 'outputs'],
    );
}

function courier_soa_workbook_script(): string
{
    return __DIR__ . '/courier_soa_workbook.py';
}

function courier_soa_user_can_access(?array $user): bool
{
    return online_parcels_user_can_access($user);
}

/** @return array{role:string,dept_id:?int} */
function courier_soa_require_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        throw new RuntimeException('Not authenticated.');
    }
    if (!courier_soa_user_can_access($user)) {
        throw new RuntimeException('Forbidden.');
    }

    return $user;
}

function courier_soa_normalize_tracking(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $tn = online_parcels_normalize_tracking($raw);
    if ($tn === '') {
        return null;
    }

    return strtoupper($tn);
}

/**
 * @return array{cmd:string, env:array<string,string>}
 */
function courier_soa_python_command(string ...$args): array
{
    $python = waybill_stamper_resolve_python();
    $script = courier_soa_workbook_script();
    if (!is_file($script)) {
        throw new RuntimeException('courier_soa_workbook.py is missing.');
    }
    $quoted = array_map(static fn (string $a): string => escapeshellarg($a), $args);
    $cmd = $python . ' ' . escapeshellarg($script) . ' ' . implode(' ', $quoted);
    $env = [];
    if (DIRECTORY_SEPARATOR === '\\') {
        $env['PYTHONIOENCODING'] = 'utf-8';
    }

    return ['cmd' => $cmd, 'env' => $env];
}

function courier_soa_write_temp_json(string $json): string
{
    $path = tempnam(sys_get_temp_dir(), 'csj_');
    if ($path === false) {
        throw new RuntimeException('Could not create temp file.');
    }
    if (file_put_contents($path, $json) === false) {
        @unlink($path);
        throw new RuntimeException('Could not write temp JSON.');
    }

    return $path;
}

/**
 * @return array<string, mixed>
 */
function courier_soa_run_python(string $mode, string $workbookPath, ?string $dstPath = null, ?string $matchesPath = null): array
{
    $args = [$mode, $workbookPath];
    if ($mode === 'highlight') {
        if ($dstPath === null || $matchesPath === null) {
            throw new RuntimeException('Highlight mode requires destination and matches paths.');
        }
        $args[] = $dstPath;
        $args[] = $matchesPath;
    }

    $built = courier_soa_python_command(...$args);
    $cmd = $built['cmd'] . ' 2>&1';
    $prevEnv = [];
    foreach ($built['env'] as $key => $val) {
        $prev = getenv($key);
        $prevEnv[$key] = $prev === false ? null : $prev;
        putenv($key . '=' . $val);
    }
    $out = shell_exec($cmd);
    foreach ($prevEnv as $key => $val) {
        if ($val === null) {
            putenv($key);
        } else {
            putenv($key . '=' . $val);
        }
    }
    if (!is_string($out) || trim($out) === '') {
        throw new RuntimeException('Python produced no output. Is openpyxl installed?');
    }
    $jsonText = trim($out);
    if ($jsonText !== '' && $jsonText[0] !== '{') {
        $start = strpos($out, '{');
        if ($start !== false) {
            $jsonText = substr($out, $start);
        }
    }
    $data = json_decode($jsonText, true);
    if (!is_array($data)) {
        throw new RuntimeException('Failed to parse Python output: ' . substr(waybill_stamper_utf8($out), 0, 400));
    }
    if (!empty($data['error'])) {
        throw new RuntimeException((string) $data['error']);
    }

    return $data;
}

function courier_soa_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['courier_soa_stash']) || !is_array($_SESSION['courier_soa_stash'])) {
        $_SESSION['courier_soa_stash'] = [];

        return;
    }
    foreach ($_SESSION['courier_soa_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['courier_soa_stash'][$token]);
        }
    }
}

function courier_soa_stash_upload(): string
{
    courier_soa_stash_prune();
    waybill_stamper_upload_preflight();
    $field = $_FILES['file'] ?? null;
    if (!is_array($field)) {
        throw new RuntimeException('No file uploaded.');
    }
    $stashDir = courier_soa_storage_root() . '/stash';
    $saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'csoa_', $stashDir);
    if ($saved === []) {
        throw new RuntimeException('No file uploaded.');
    }
    $path = $saved[0];
    $token = bin2hex(random_bytes(16));
    $originalName = '';
    if (isset($field['name'])) {
        $originalName = is_array($field['name']) ? (string) ($field['name'][0] ?? '') : (string) $field['name'];
    }
    ims_session_start();
    if (!isset($_SESSION['courier_soa_stash']) || !is_array($_SESSION['courier_soa_stash'])) {
        $_SESSION['courier_soa_stash'] = [];
    }
    $_SESSION['courier_soa_stash'][$token] = [
        'path' => $path,
        'original_name' => $originalName,
        'expires' => time() + 3600,
    ];

    return $token;
}

function courier_soa_resolve_stash_path(string $token): string
{
    courier_soa_stash_prune();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token)) ?? '';
    if ($token === '') {
        throw new RuntimeException('Invalid upload token.');
    }
    $stash = $_SESSION['courier_soa_stash'] ?? [];
    if (!is_array($stash)) {
        throw new RuntimeException('Invalid or expired upload token.');
    }
    $meta = $stash[$token] ?? null;
    if (!is_array($meta)) {
        throw new RuntimeException('Invalid or expired upload token.');
    }
    $path = (string) ($meta['path'] ?? '');
    if ($path === '' || !is_file($path)) {
        throw new RuntimeException('Stashed file missing. Upload again.');
    }

    return $path;
}

function courier_soa_stash_original_name(string $token): string
{
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token)) ?? '';
    $meta = $_SESSION['courier_soa_stash'][$token] ?? null;
    if (!is_array($meta)) {
        return 'courier-soa.xlsx';
    }

    return (string) ($meta['original_name'] ?? 'courier-soa.xlsx');
}

/**
 * @return array{
 *   by_tn: array<string, array{tracking_number:string, ship_date:?string, order_no:string, courier:string}>,
 *   set: array<string, true>
 * }
 */
function courier_soa_shipped_tracking_map(PDO $pdo, string $courierKey, string $dateFrom, string $dateTo): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        throw new RuntimeException('date_from and date_to required (YYYY-MM-DD).');
    }
    if ($dateFrom > $dateTo) {
        throw new RuntimeException('date_from must be on or before date_to.');
    }
    if ($courierKey !== 'jnt' && $courierKey !== 'spx') {
        throw new RuntimeException('Unsupported courier format.');
    }

    online_parcels_ensure_schema($pdo);
    $st = $pdo->prepare(
        'SELECT tracking_number, ship_date, order_no, courier
         FROM online_order_parcels
         WHERE status = ?
           AND ship_date IS NOT NULL
           AND ship_date >= ?
           AND ship_date <= ?
           AND tracking_number IS NOT NULL
           AND tracking_number <> ""'
    );
    $st->execute(['shipped', $dateFrom, $dateTo]);

    $byTn = [];
    $set = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $bucket = rts_courier_label_bucket((string) ($row['courier'] ?? ''));
        if ($bucket !== $courierKey) {
            continue;
        }
        $norm = courier_soa_normalize_tracking((string) ($row['tracking_number'] ?? ''));
        if ($norm === null) {
            continue;
        }
        $set[$norm] = true;
        if (!isset($byTn[$norm])) {
            $byTn[$norm] = [
                'tracking_number' => $norm,
                'ship_date' => isset($row['ship_date']) ? (string) $row['ship_date'] : null,
                'order_no' => (string) ($row['order_no'] ?? ''),
                'courier' => (string) ($row['courier'] ?? ''),
            ];
        }
    }

    return ['by_tn' => $byTn, 'set' => $set];
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function courier_soa_reconcile(PDO $pdo, array $input): array
{
    $token = trim((string) ($input['token'] ?? ''));
    if ($token === '') {
        throw new RuntimeException('token required.');
    }
    $dateFrom = trim((string) ($input['date_from'] ?? ''));
    $dateTo = trim((string) ($input['date_to'] ?? ''));
    if ($dateFrom === '' || $dateTo === '') {
        throw new RuntimeException('date_from and date_to required.');
    }

    $srcPath = courier_soa_resolve_stash_path($token);
    $scan = courier_soa_run_python('scan', $srcPath);
    $format = strtolower(trim((string) ($scan['format'] ?? '')));
    if ($format !== 'jnt' && $format !== 'spx') {
        throw new RuntimeException('Could not detect J&T or SPX SOA format.');
    }

    $rows = $scan['rows'] ?? [];
    if (!is_array($rows)) {
        $rows = [];
    }

    $ours = courier_soa_shipped_tracking_map($pdo, $format, $dateFrom, $dateTo);
    $ourSet = $ours['set'];
    $ourByTn = $ours['by_tn'];

    $soaSet = [];
    $highlightRows = [];
    $extraInSoa = [];
    $soaRowsWithTn = 0;
    $soaDuplicates = 0;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $excelRow = (int) ($row['row'] ?? 0);
        $tracking = courier_soa_normalize_tracking(isset($row['tracking']) ? (string) $row['tracking'] : null);
        if ($tracking === null) {
            continue;
        }
        $soaRowsWithTn++;
        if (!empty($row['duplicate'])) {
            $soaDuplicates++;
        }
        $soaSet[$tracking] = true;
        $status = isset($ourSet[$tracking]) ? 'matched' : 'missing';
        if ($status === 'missing' && count($extraInSoa) < 100) {
            $alreadyListed = false;
            foreach ($extraInSoa as $ex) {
                if (($ex['tracking_number'] ?? '') === $tracking) {
                    $alreadyListed = true;
                    break;
                }
            }
            if (!$alreadyListed) {
                $extraInSoa[] = [
                    'row' => $excelRow,
                    'tracking_number' => $tracking,
                ];
            }
        }
        if ($excelRow > 0) {
            $highlightRows[] = ['row' => $excelRow, 'status' => $status];
        }
    }

    $missingFromSoa = [];
    foreach ($ourByTn as $tn => $meta) {
        if (!isset($soaSet[$tn]) && count($missingFromSoa) < 100) {
            $missingFromSoa[] = $meta;
        }
    }

    $matchedUnique = count(array_intersect_key($soaSet, $ourSet));
    $missingCount = count($ourSet) - $matchedUnique;
    $extraCount = count($soaSet) - $matchedUnique;

    $outDir = courier_soa_storage_root() . '/outputs';
    if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
        throw new RuntimeException('Could not create output directory.');
    }
    $outPath = $outDir . '/reconciled_' . bin2hex(random_bytes(8)) . '.xlsx';
    $matchesPayload = json_encode([
        'header_row' => (int) ($scan['header_row'] ?? 1),
        'track_col' => (int) ($scan['track_col'] ?? 1),
        'rows' => $highlightRows,
    ], JSON_UNESCAPED_UNICODE);
    if ($matchesPayload === false) {
        throw new RuntimeException('Could not encode highlight payload.');
    }
    $matchesPath = courier_soa_write_temp_json($matchesPayload);
    try {
        courier_soa_run_python('highlight', $srcPath, $outPath, $matchesPath);
    } finally {
        @unlink($matchesPath);
    }

    $original = courier_soa_stash_original_name($token);
    $base = pathinfo($original, PATHINFO_FILENAME);
    if ($base === '') {
        $base = 'courier-soa';
    }
    $downloadFilename = $base . '-reconciled.xlsx';
    $downloadToken = courier_soa_register_download($outPath, $downloadFilename);

    return [
        'summary' => [
            'soa_rows' => $soaRowsWithTn,
            'soa_unique' => count($soaSet),
            'soa_duplicates' => $soaDuplicates,
            'our_shipped' => count($ourSet),
            'matched' => $matchedUnique,
            'missing_from_soa' => $missingCount,
            'extra_in_soa' => $extraCount,
        ],
        'format' => $format,
        'courier_label' => $format === 'jnt' ? 'J&T' : 'SPX',
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'sheet' => (string) ($scan['sheet'] ?? ''),
        'download_token' => $downloadToken,
        'download_filename' => $downloadFilename,
        'missing_from_soa' => $missingFromSoa,
        'extra_in_soa' => $extraInSoa,
    ];
}

function courier_soa_register_download(string $path, string $filename): string
{
    ims_session_start();
    if (!isset($_SESSION['courier_soa_downloads']) || !is_array($_SESSION['courier_soa_downloads'])) {
        $_SESSION['courier_soa_downloads'] = [];
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['courier_soa_downloads'][$token] = [
        'path' => $path,
        'filename' => $filename,
        'expires' => time() + 3600,
    ];

    return $token;
}

function courier_soa_serve_download(string $token): void
{
    courier_soa_require_access();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token)) ?? '';
    $meta = $_SESSION['courier_soa_downloads'][$token] ?? null;
    if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
        http_response_code(404);
        echo 'Download expired or not found.';
        exit;
    }
    $path = (string) ($meta['path'] ?? '');
    $filename = (string) ($meta['filename'] ?? 'courier-soa-reconciled.xlsx');
    if ($path === '' || !is_file($path)) {
        http_response_code(404);
        echo 'File not found.';
        exit;
    }
    unset($_SESSION['courier_soa_downloads'][$token]);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}
