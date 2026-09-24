<?php

declare(strict_types=1);

/**
 * Partner seller sold-list reconciliation vs Online Parcels.
 * Directions: ours_in_theirs (default) | theirs_in_ours.
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

function partner_sold_normalize_tracking(?string $raw): ?string
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

    return $text;
}

/**
 * Lookup keys for a numeric marketplace order id (exact + float round-trip).
 *
 * @return list<string>
 */
function partner_sold_order_no_lookup_keys(?string $norm): array
{
    if ($norm === null || $norm === '') {
        return [];
    }
    $keys = [$norm];
    if (preg_match('/^\d{15,}$/', $norm)) {
        $floatKey = sprintf('%.0f', (float) $norm);
        if ($floatKey !== '' && $floatKey !== $norm) {
            $keys[] = $floatKey;
        }
    }

    return array_values(array_unique($keys));
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
function partner_sold_run_python(string $mode, string $pathA, ?string $pathB = null, ?string $pathC = null): array
{
    $args = [$mode, $pathA];
    if ($mode === 'highlight') {
        if ($pathB === null || $pathC === null) {
            throw new RuntimeException('Highlight mode requires destination and matches paths.');
        }
        $args[] = $pathB;
        $args[] = $pathC;
    } elseif ($mode === 'export_ours') {
        if ($pathB === null) {
            throw new RuntimeException('export_ours requires destination path.');
        }
        $args[] = $pathB;
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
 * Existence indexes for a partner (any parcel status).
 *
 * @return array{order_nos: array<string, true>, trackings: array<string, true>}
 */
function partner_sold_existing_parcel_indexes(
    PDO $pdo,
    string $partnerSeller,
    ?string $dateFrom = null,
    ?string $dateTo = null,
): array {
    $parcels = partner_sold_list_our_parcels($pdo, $partnerSeller, $dateFrom, $dateTo);
    $orderNos = [];
    $trackings = [];
    foreach ($parcels as $row) {
        $norm = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);
        foreach (partner_sold_order_no_lookup_keys($norm) as $key) {
            $orderNos[$key] = true;
        }
        $tn = partner_sold_normalize_tracking(isset($row['tracking_number']) ? (string) $row['tracking_number'] : null);
        if ($tn !== null && $tn !== '') {
            $trackings[$tn] = true;
            $trackings[strtoupper($tn)] = true;
        }
    }

    return ['order_nos' => $orderNos, 'trackings' => $trackings];
}

/**
 * Our Online Parcels for a partner (any status), optional date window.
 *
 * @return list<array{id:int, order_no:?string, tracking_number:?string, status:string, ship_date:?string, created_at:?string}>
 */
function partner_sold_list_our_parcels(
    PDO $pdo,
    string $partnerSeller,
    ?string $dateFrom = null,
    ?string $dateTo = null,
): array {
    online_parcels_ensure_schema($pdo);
    $sql = 'SELECT id, order_no, tracking_number, status, ship_date, created_at FROM online_order_parcels
            WHERE partner_seller = ?';
    $params = [$partnerSeller];
    $hasFrom = $dateFrom !== null && $dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom);
    $hasTo = $dateTo !== null && $dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo);
    if ($hasFrom || $hasTo) {
        if ($hasFrom && $hasTo) {
            $sql .= ' AND ((ship_date IS NOT NULL AND ship_date >= ? AND ship_date <= ?)
                OR (ship_date IS NULL AND DATE(created_at) >= ? AND DATE(created_at) <= ?))';
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } elseif ($hasFrom) {
            $sql .= ' AND ((ship_date IS NOT NULL AND ship_date >= ?)
                OR (ship_date IS NULL AND DATE(created_at) >= ?))';
            $params[] = $dateFrom;
            $params[] = $dateFrom;
        } else {
            $sql .= ' AND ((ship_date IS NOT NULL AND ship_date <= ?)
                OR (ship_date IS NULL AND DATE(created_at) <= ?))';
            $params[] = $dateTo;
            $params[] = $dateTo;
        }
    }
    $sql .= ' ORDER BY id ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $out = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $out[] = [
            'id' => (int) ($row['id'] ?? 0),
            'order_no' => isset($row['order_no']) && $row['order_no'] !== null && $row['order_no'] !== ''
                ? (string) $row['order_no'] : null,
            'tracking_number' => isset($row['tracking_number']) && $row['tracking_number'] !== null && $row['tracking_number'] !== ''
                ? (string) $row['tracking_number'] : null,
            'status' => (string) ($row['status'] ?? ''),
            'ship_date' => isset($row['ship_date']) && $row['ship_date'] !== null ? (string) $row['ship_date'] : null,
            'created_at' => isset($row['created_at']) && $row['created_at'] !== null ? (string) $row['created_at'] : null,
        ];
    }

    return $out;
}

/**
 * Build Order No / TN indexes from a scanned partner sold-list Excel.
 *
 * @param list<mixed> $rows
 * @return array{order_nos: array<string, true>, trackings: array<string, true>, excluded: int, with_order_no: int, skipped: int}
 */
function partner_sold_indexes_from_excel_rows(array $rows): array
{
    $orderNos = [];
    $trackings = [];
    $excluded = 0;
    $withOrderNo = 0;
    $skipped = 0;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);
        $tracking = partner_sold_normalize_tracking(
            isset($row['tracking_number']) ? (string) $row['tracking_number'] : null
        );
        if (!empty($row['excluded'])) {
            $excluded++;
            continue;
        }
        if (($orderNo === null || $orderNo === '') && ($tracking === null || $tracking === '')) {
            $skipped++;
            continue;
        }
        if ($orderNo !== null && $orderNo !== '') {
            $withOrderNo++;
            foreach (partner_sold_order_no_lookup_keys($orderNo) as $key) {
                $orderNos[$key] = true;
            }
        }
        if ($tracking !== null && $tracking !== '') {
            $trackings[$tracking] = true;
            $trackings[strtoupper($tracking)] = true;
        }
    }

    return [
        'order_nos' => $orderNos,
        'trackings' => $trackings,
        'excluded' => $excluded,
        'with_order_no' => $withOrderNo,
        'skipped' => $skipped,
    ];
}

/**
 * @deprecated Use partner_sold_existing_parcel_indexes(); kept for callers/tests expecting the old name.
 * @return array<string, true>
 */
function partner_sold_shipped_order_no_set(
    PDO $pdo,
    string $partnerSeller,
    ?string $dateFrom = null,
    ?string $dateTo = null,
): array {
    return partner_sold_existing_parcel_indexes($pdo, $partnerSeller, $dateFrom, $dateTo)['order_nos'];
}

/**
 * @param array<string, true> $orderNos
 * @param array<string, true> $trackings
 */
function partner_sold_row_exists(array $orderNos, array $trackings, ?string $orderNo, ?string $tracking): bool
{
    foreach (partner_sold_order_no_lookup_keys($orderNo) as $key) {
        if (isset($orderNos[$key])) {
            return true;
        }
    }
    if ($tracking !== null && $tracking !== '') {
        if (isset($trackings[$tracking]) || isset($trackings[strtoupper($tracking)])) {
            return true;
        }
    }

    return false;
}

function partner_sold_normalize_direction(string $raw): string
{
    $d = strtolower(trim($raw));
    if ($d === 'theirs_in_ours' || $d === 'theirs') {
        return 'theirs_in_ours';
    }

    return 'ours_in_theirs';
}

/**
 * @param array<string, mixed> $scan
 * @return array{
 *   summary: array<string, int>,
 *   partner_seller: string,
 *   direction: string,
 *   sheet: string,
 *   download_token: string,
 *   download_filename: string,
 *   preview: list<array<string, mixed>>
 * }
 */
function partner_sold_reconcile_theirs_in_ours(
    PDO $pdo,
    string $partnerSeller,
    string $srcPath,
    string $token,
    array $scan,
    ?string $dateFrom,
    ?string $dateTo,
): array {
    $rows = $scan['rows'] ?? [];
    if (!is_array($rows)) {
        $rows = [];
    }

    $indexes = partner_sold_existing_parcel_indexes($pdo, $partnerSeller, $dateFrom, $dateTo);
    $orderNos = $indexes['order_nos'];
    $trackings = $indexes['trackings'];

    $summary = [
        'total_rows' => count($rows),
        'rows_with_order_no' => 0,
        'excluded_status' => 0,
        'checked' => 0,
        'matched' => 0,
        'not_found' => 0,
        'skipped_no_order_no' => 0,
        'skipped_blank_rows' => (int) ($scan['skipped_blank_rows'] ?? 0),
        'our_parcels' => 0,
    ];
    $highlightRows = [];
    $preview = [];

    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $excelRow = (int) ($row['row'] ?? 0);
        $orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);
        $tracking = partner_sold_normalize_tracking(
            isset($row['tracking_number']) ? (string) $row['tracking_number'] : null
        );
        $excluded = !empty($row['excluded']);
        $status = 'skip';

        if (($orderNo === null || $orderNo === '') && ($tracking === null || $tracking === '')) {
            $summary['skipped_no_order_no']++;
        } else {
            if ($orderNo !== null && $orderNo !== '') {
                $summary['rows_with_order_no']++;
            }
            if ($excluded) {
                $summary['excluded_status']++;
                $status = 'skip';
            } else {
                $summary['checked']++;
                if (partner_sold_row_exists($orderNos, $trackings, $orderNo, $tracking)) {
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
                'tracking_number' => $tracking,
                'parcel_status' => null,
                'ship_date' => null,
                'created_at' => null,
                'remark' => (string) ($row['remark'] ?? ''),
                'excluded' => $excluded,
                'matched' => $status === 'matched',
                'exists' => $status === 'matched',
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
        'direction' => 'theirs_in_ours',
        'sheet' => (string) ($scan['sheet'] ?? ''),
        'download_token' => $downloadToken,
        'download_filename' => $downloadFilename,
        'preview' => $preview,
    ];
}

/**
 * @param array<string, mixed> $scan
 * @return array{
 *   summary: array<string, int>,
 *   partner_seller: string,
 *   direction: string,
 *   sheet: string,
 *   download_token: string,
 *   download_filename: string,
 *   preview: list<array<string, mixed>>
 * }
 */
function partner_sold_reconcile_ours_in_theirs(
    PDO $pdo,
    string $partnerSeller,
    string $token,
    array $scan,
    ?string $dateFrom,
    ?string $dateTo,
): array {
    $excelRows = $scan['rows'] ?? [];
    if (!is_array($excelRows)) {
        $excelRows = [];
    }
    $excelIdx = partner_sold_indexes_from_excel_rows($excelRows);
    $theirOrderNos = $excelIdx['order_nos'];
    $theirTrackings = $excelIdx['trackings'];

    $parcels = partner_sold_list_our_parcels($pdo, $partnerSeller, $dateFrom, $dateTo);
    $summary = [
        'total_rows' => count($excelRows),
        'rows_with_order_no' => $excelIdx['with_order_no'],
        'excluded_status' => $excelIdx['excluded'],
        'checked' => 0,
        'matched' => 0,
        'not_found' => 0,
        'skipped_no_order_no' => $excelIdx['skipped'],
        'skipped_blank_rows' => (int) ($scan['skipped_blank_rows'] ?? 0),
        'our_parcels' => count($parcels),
    ];
    $exportRows = [];
    $preview = [];

    foreach ($parcels as $parcel) {
        $orderNo = partner_sold_normalize_order_no(
            isset($parcel['order_no']) ? (string) $parcel['order_no'] : null
        );
        $tracking = partner_sold_normalize_tracking(
            isset($parcel['tracking_number']) ? (string) $parcel['tracking_number'] : null
        );
        if (($orderNo === null || $orderNo === '') && ($tracking === null || $tracking === '')) {
            continue;
        }
        $summary['checked']++;
        $exists = partner_sold_row_exists($theirOrderNos, $theirTrackings, $orderNo, $tracking);
        if ($exists) {
            $summary['matched']++;
            $status = 'matched';
        } else {
            $summary['not_found']++;
            $status = 'missing';
        }
        $exportRows[] = [
            'order_no' => $orderNo,
            'tracking_number' => $tracking,
            'parcel_status' => (string) ($parcel['status'] ?? ''),
            'ship_date' => $parcel['ship_date'] ?? null,
            'created_at' => $parcel['created_at'] ?? null,
            'status' => $status,
        ];
        if (count($preview) < 25) {
            $preview[] = [
                'row' => count($preview) + 1,
                'order_no' => $orderNo,
                'tracking_number' => $tracking,
                'parcel_status' => (string) ($parcel['status'] ?? ''),
                'ship_date' => $parcel['ship_date'] ?? null,
                'created_at' => $parcel['created_at'] ?? null,
                'remark' => '',
                'excluded' => false,
                'matched' => $exists,
                'exists' => $exists,
                'status' => $status,
            ];
        }
    }

    $outDir = partner_sold_storage_root() . '/outputs';
    if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
        throw new RuntimeException('Could not create output directory.');
    }
    $outPath = $outDir . '/ours_in_theirs_' . bin2hex(random_bytes(8)) . '.xlsx';
    $payload = json_encode([
        'sheet_title' => 'Our parcels',
        'rows' => $exportRows,
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        throw new RuntimeException('Could not encode export payload.');
    }
    $rowsPath = partner_sold_write_temp_json($payload);
    try {
        partner_sold_run_python('export_ours', $rowsPath, $outPath);
    } finally {
        @unlink($rowsPath);
    }

    $downloadFilename = 'our-parcels-in-' . $partnerSeller . '-sold-list.xlsx';
    $downloadToken = partner_sold_register_download($outPath, $downloadFilename);

    return [
        'summary' => $summary,
        'partner_seller' => $partnerSeller,
        'direction' => 'ours_in_theirs',
        'sheet' => (string) ($scan['sheet'] ?? ''),
        'download_token' => $downloadToken,
        'download_filename' => $downloadFilename,
        'preview' => $preview,
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *   summary: array<string, int>,
 *   partner_seller: string,
 *   direction: string,
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
    $direction = partner_sold_normalize_direction((string) ($input['direction'] ?? 'ours_in_theirs'));
    $dateFrom = trim((string) ($input['date_from'] ?? ''));
    $dateTo = trim((string) ($input['date_to'] ?? ''));
    $from = $dateFrom !== '' ? $dateFrom : null;
    $to = $dateTo !== '' ? $dateTo : null;
    $srcPath = partner_sold_resolve_stash_path($token);
    $scan = partner_sold_run_python('scan', $srcPath);

    if ($direction === 'theirs_in_ours') {
        return partner_sold_reconcile_theirs_in_ours($pdo, $partnerSeller, $srcPath, $token, $scan, $from, $to);
    }

    return partner_sold_reconcile_ours_in_theirs($pdo, $partnerSeller, $token, $scan, $from, $to);
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
