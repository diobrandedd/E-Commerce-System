<?php

declare(strict_types=1);

/**
 * Partner seller sold-list reconciliation vs shipped Online Parcels.
 */

require_once __DIR__ . '/online_order_parcels.php';
require_once __DIR__ . '/online_orders_generator.php';
require_once __DIR__ . '/waybill_stamper.php';

function partner_sold_storage_root(): string
{
    return rts_storage_csr_tool_root(
        RTS_STORAGE_BUCKET_PARTNER_SOLD,
        'partner-sold',
        ['stash', 'outputs'],
    );
}

function partner_sold_workbook_script(): string
{
    return __DIR__ . '/partner_sold_workbook.py';
}

function partner_sold_user_can_access(?array $user): bool
{
    return online_parcels_user_can_access($user);
}

/** @return array{role:string,dept_id:?int} */
function partner_sold_require_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        throw new RuntimeException('Not authenticated.');
    }
    if (!partner_sold_user_can_access($user)) {
        throw new RuntimeException('Forbidden.');
    }

    return $user;
}

function partner_sold_normalize_order_no(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $text = trim($raw);
    if ($text === '') {
        return null;
    }
    if (preg_match('/^\d+\.0$/', $text)) {
        $text = substr($text, 0, -2);
    }
    if (preg_match('/^\d+\.?\d*[eE][+]?\d+$/', $text)) {
        $f = (float) $text;
        if (is_finite($f)) {
            $text = sprintf('%.0f', $f);
        }
    }
    if (preg_match('/^\d+$/', $text)) {
        return $text;
    }

    return $text;
}

/**
 * @return array{cmd:string, env:array<string,string>}
 */
function partner_sold_python_command(string ...$args): array
{
    $python = waybill_stamper_resolve_python();
    $script = partner_sold_workbook_script();
    if (!is_file($script)) {
        throw new RuntimeException('partner_sold_workbook.py is missing.');
    }
    $quoted = array_map(static fn (string $a): string => escapeshellarg($a), $args);
    $cmd = $python . ' ' . escapeshellarg($script) . ' ' . implode(' ', $quoted);
    $env = [];
    if (DIRECTORY_SEPARATOR === '\\') {
        $env['PYTHONIOENCODING'] = 'utf-8';
    }

    return ['cmd' => $cmd, 'env' => $env];
}

function partner_sold_write_temp_json(string $json): string
{
    $path = tempnam(sys_get_temp_dir(), 'psj_');
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
function partner_sold_run_python(string $mode, string $workbookPath, ?string $dstPath = null, ?string $matchesPath = null): array
{
    $args = [$mode, $workbookPath];
    if ($mode === 'highlight') {
        if ($dstPath === null || $matchesPath === null) {
            throw new RuntimeException('Highlight mode requires destination and matches paths.');
        }
        $args[] = $dstPath;
        $args[] = $matchesPath;
    }

    $built = partner_sold_python_command(...$args);
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

function partner_sold_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['partner_sold_stash']) || !is_array($_SESSION['partner_sold_stash'])) {
        $_SESSION['partner_sold_stash'] = [];

        return;
    }
    foreach ($_SESSION['partner_sold_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['partner_sold_stash'][$token]);
        }
    }
}

function partner_sold_stash_upload(): string
{
    partner_sold_stash_prune();
    waybill_stamper_upload_preflight();
    $field = $_FILES['file'] ?? null;
    if (!is_array($field)) {
        throw new RuntimeException('No file uploaded.');
    }
    $stashDir = partner_sold_storage_root() . '/stash';
    $saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'ps_', $stashDir);
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
    if (!isset($_SESSION['partner_sold_stash']) || !is_array($_SESSION['partner_sold_stash'])) {
        $_SESSION['partner_sold_stash'] = [];
    }
    $_SESSION['partner_sold_stash'][$token] = [
        'path' => $path,
        'original_name' => $originalName,
        'expires' => time() + 3600,
    ];

    return $token;
}

function partner_sold_resolve_stash_path(string $token): string
{
    partner_sold_stash_prune();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    if ($token === '') {
        throw new RuntimeException('Invalid upload token.');
    }
    $stash = $_SESSION['partner_sold_stash'] ?? [];
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

function partner_sold_stash_original_name(string $token): string
{
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    $meta = $_SESSION['partner_sold_stash'][$token] ?? null;
    if (!is_array($meta)) {
        return 'partner-sold-list.xlsx';
    }

    return (string) ($meta['original_name'] ?? 'partner-sold-list.xlsx');
}

/**
 * @return array<string, true>
 */
function partner_sold_shipped_order_no_set(
    PDO $pdo,
    string $partnerSeller,
    ?string $dateFrom = null,
    ?string $dateTo = null,
): array {
    online_parcels_ensure_schema($pdo);
    $sql = 'SELECT DISTINCT order_no FROM online_order_parcels
            WHERE partner_seller = ? AND status = ? AND order_no IS NOT NULL AND order_no <> ""';
    $params = [$partnerSeller, 'shipped'];
    if ($dateFrom !== null && $dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $sql .= ' AND ship_date >= ?';
        $params[] = $dateFrom;
    }
    if ($dateTo !== null && $dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $sql .= ' AND ship_date <= ?';
        $params[] = $dateTo;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $set = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $norm = partner_sold_normalize_order_no((string) ($row['order_no'] ?? ''));
        if ($norm !== null && $norm !== '') {
            $set[$norm] = true;
        }
    }

    return $set;
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *   summary: array<string, int>,
 *   partner_seller: string,
 *   download_token: string,
 *   download_filename: string,
 *   preview: list<array<string, mixed>>
 * }
 */
function partner_sold_reconcile(PDO $pdo, array $input): array
{
    $token = trim((string) ($input['token'] ?? ''));
    if ($token === '') {
        throw new RuntimeException('token required.');
    }
    $partnerSeller = online_orders_normalize_partner_seller((string) ($input['partner_seller'] ?? ''));
    if ($partnerSeller === null) {
        throw new RuntimeException('partner_seller required (ian, jono, or joven).');
    }
    $dateFrom = trim((string) ($input['date_from'] ?? ''));
    $dateTo = trim((string) ($input['date_to'] ?? ''));
    $srcPath = partner_sold_resolve_stash_path($token);
    $scan = partner_sold_run_python('scan', $srcPath);
    $rows = $scan['rows'] ?? [];
    if (!is_array($rows)) {
        $rows = [];
    }

    $shipped = partner_sold_shipped_order_no_set(
        $pdo,
        $partnerSeller,
        $dateFrom !== '' ? $dateFrom : null,
        $dateTo !== '' ? $dateTo : null,
    );

    $summary = [
        'total_rows' => count($rows),
        'rows_with_order_no' => 0,
        'excluded_status' => 0,
        'checked' => 0,
        'matched' => 0,
        'not_found' => 0,
        'skipped_no_order_no' => 0,
    ];
    $highlightRows = [];
    $preview = [];

    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $excelRow = (int) ($row['row'] ?? 0);
        $orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);
        $excluded = !empty($row['excluded']);
        $status = 'skip';

        if ($orderNo === null || $orderNo === '') {
            $summary['skipped_no_order_no']++;
        } else {
            $summary['rows_with_order_no']++;
            if ($excluded) {
                $summary['excluded_status']++;
                $status = 'skip';
            } else {
                $summary['checked']++;
                if (isset($shipped[$orderNo])) {
                    $summary['matched']++;
                    $status = 'matched';
                } else {
                    $summary['not_found']++;
                    $status = 'missing';
                }
            }
        }

        if ($excelRow > 0 && ($status === 'matched' || $status === 'missing')) {
            $highlightRows[] = ['row' => $excelRow, 'status' => $status];
        }
        if ($idx < 25) {
            $preview[] = [
                'row' => $excelRow,
                'order_no' => $orderNo,
                'remark' => (string) ($row['remark'] ?? ''),
                'excluded' => $excluded,
                'matched' => $status === 'matched',
                'status' => $status,
            ];
        }
    }

    $outDir = partner_sold_storage_root() . '/outputs';
    if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
        throw new RuntimeException('Could not create output directory.');
    }
    $outPath = $outDir . '/reconciled_' . bin2hex(random_bytes(8)) . '.xlsx';
    $matchesPayload = json_encode([
        'header_row' => (int) ($scan['header_row'] ?? 1),
        'order_col' => (int) ($scan['order_col'] ?? 2),
        'rows' => $highlightRows,
    ], JSON_UNESCAPED_UNICODE);
    if ($matchesPayload === false) {
        throw new RuntimeException('Could not encode highlight payload.');
    }
    $matchesPath = partner_sold_write_temp_json($matchesPayload);
    try {
        partner_sold_run_python('highlight', $srcPath, $outPath, $matchesPath);
    } finally {
        @unlink($matchesPath);
    }

    $original = partner_sold_stash_original_name($token);
    $base = pathinfo($original, PATHINFO_FILENAME);
    if ($base === '') {
        $base = 'partner-sold-list';
    }
    $downloadFilename = $base . '-reconciled.xlsx';
    $downloadToken = partner_sold_register_download($outPath, $downloadFilename);

    return [
        'summary' => $summary,
        'partner_seller' => $partnerSeller,
        'sheet' => (string) ($scan['sheet'] ?? ''),
        'download_token' => $downloadToken,
        'download_filename' => $downloadFilename,
        'preview' => $preview,
    ];
}

function partner_sold_register_download(string $path, string $filename): string
{
    ims_session_start();
    if (!isset($_SESSION['partner_sold_downloads']) || !is_array($_SESSION['partner_sold_downloads'])) {
        $_SESSION['partner_sold_downloads'] = [];
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['partner_sold_downloads'][$token] = [
        'path' => $path,
        'filename' => $filename,
        'expires' => time() + 3600,
    ];

    return $token;
}

function partner_sold_serve_download(string $token): void
{
    partner_sold_require_access();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    $meta = $_SESSION['partner_sold_downloads'][$token] ?? null;
    if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
        http_response_code(404);
        echo 'Download expired or not found.';
        exit;
    }
    $path = (string) ($meta['path'] ?? '');
    $filename = (string) ($meta['filename'] ?? 'partner-sold-reconciled.xlsx');
    if ($path === '' || !is_file($path)) {
        http_response_code(404);
        echo 'File not found.';
        exit;
    }
    unset($_SESSION['partner_sold_downloads'][$token]);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}
