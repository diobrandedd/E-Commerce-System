<?php

declare(strict_types=1);

/**
 * Historical Snappy/Webcake shipped Excel (with Tracking number) → online_order_parcels backfill.
 * Two-step: preview (with TN conflicts) → commit (insert new / keep or overwrite conflicts).
 */

require_once __DIR__ . '/online_order_parcels.php';
require_once __DIR__ . '/online_orders_generator.php';
require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/waybill_stamper.php';
require_once __DIR__ . '/rts_storage.php';

const HISTORICAL_ORDERS_PIPELINES = ['vip', 'partner', 'shopee_spx', 'tiktok'];

function historical_orders_storage_root(): string
{
    return rts_storage_csr_tool_root(
        RTS_STORAGE_BUCKET_HISTORICAL_ORDERS,
        'historical-orders',
        ['stash'],
    );
}

function historical_orders_workbook_script(): string
{
    return __DIR__ . '/historical_orders_workbook.py';
}

/**
 * @return array{cmd:string, env:array<string,string>}
 */
function historical_orders_python_command(string ...$args): array
{
    $python = waybill_stamper_resolve_python();
    $script = historical_orders_workbook_script();
    if (!is_file($script)) {
        throw new RuntimeException('historical_orders_workbook.py is missing.');
    }
    $quoted = array_map(static fn (string $a): string => escapeshellarg($a), $args);
    $cmd = $python . ' ' . escapeshellarg($script) . ' ' . implode(' ', $quoted);
    $env = [];
    if (DIRECTORY_SEPARATOR === '\\') {
        $env['PYTHONIOENCODING'] = 'utf-8';
    }

    return ['cmd' => $cmd, 'env' => $env];
}

/**
 * @return array<string, mixed>
 */
function historical_orders_run_python_parse(string $workbookPath, ?string $sheetName = null): array
{
    $args = ['parse', $workbookPath];
    if ($sheetName !== null && trim($sheetName) !== '') {
        $args[] = trim($sheetName);
    }
    $built = historical_orders_python_command(...$args);
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
        throw new RuntimeException('Failed to parse workbook output: ' . substr(waybill_stamper_utf8($out), 0, 400));
    }
    if (!empty($data['error'])) {
        throw new RuntimeException((string) $data['error']);
    }

    return $data;
}

function historical_orders_user_can_access(?array $user): bool
{
    return online_parcels_user_can_access($user);
}

/** @return array{role:string,dept_id:?int} */
function historical_orders_require_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        throw new RuntimeException('Not authenticated.');
    }
    if (!historical_orders_user_can_access($user)) {
        throw new RuntimeException('Forbidden.');
    }

    return $user;
}

function historical_orders_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['historical_orders_stash']) || !is_array($_SESSION['historical_orders_stash'])) {
        $_SESSION['historical_orders_stash'] = [];

        return;
    }
    foreach ($_SESSION['historical_orders_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            $p = (string) ($meta['path'] ?? '');
            if ($p !== '' && is_file($p)) {
                @unlink($p);
            }
            unset($_SESSION['historical_orders_stash'][$token]);
        }
    }
}

function historical_orders_stash_upload(): string
{
    historical_orders_stash_prune();
    waybill_stamper_upload_preflight();

    $field = $_FILES['file'] ?? null;
    if (!is_array($field)) {
        throw new RuntimeException('No file uploaded.');
    }

    $stashDir = historical_orders_storage_root() . '/stash';
    $saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'hist_', $stashDir);
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
    if (!isset($_SESSION['historical_orders_stash']) || !is_array($_SESSION['historical_orders_stash'])) {
        $_SESSION['historical_orders_stash'] = [];
    }
    $_SESSION['historical_orders_stash'][$token] = [
        'path' => $path,
        'original_name' => $originalName,
        'expires' => time() + 3600,
    ];

    return $token;
}

function historical_orders_resolve_stash_path(string $token): string
{
    historical_orders_stash_prune();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token)) ?? '';
    if ($token === '') {
        throw new RuntimeException('Invalid upload token.');
    }
    ims_session_start();
    $meta = $_SESSION['historical_orders_stash'][$token] ?? null;
    if (!is_array($meta)) {
        throw new RuntimeException('Upload expired or not found. Upload the Excel again.');
    }
    $path = (string) ($meta['path'] ?? '');
    if ($path === '' || !is_file($path)) {
        throw new RuntimeException('Upload file missing. Upload the Excel again.');
    }

    return $path;
}

/**
 * @return array{year:int,month:int,ship_date:string,yyyymm:string,label:string}
 */
function historical_orders_resolve_month(int $year, int $month): array
{
    if ($year < 2020 || $year > 2100) {
        throw new RuntimeException('Invalid year.');
    }
    if ($month < 1 || $month > 12) {
        throw new RuntimeException('Invalid month.');
    }
    $first = DateTimeImmutable::createFromFormat('!Y-n-j', $year . '-' . $month . '-1');
    if ($first === false) {
        throw new RuntimeException('Invalid month.');
    }
    $shipDate = $first->modify('last day of this month')->format('Y-m-d');
    $yyyymm = $first->format('Ym');
    $label = $first->format('F Y');

    return [
        'year' => $year,
        'month' => $month,
        'ship_date' => $shipDate,
        'yyyymm' => $yyyymm,
        'label' => $label,
    ];
}

/**
 * Prefer real courier TN from Excel. Plain alphanumeric (no invented VIP- prefix).
 */
function historical_orders_normalize_tn(string $raw): string
{
    $tn = preg_replace('/\s+/', '', trim($raw)) ?? '';
    $tn = preg_replace('/[^A-Za-z0-9]/', '', $tn) ?? '';

    return $tn;
}

function historical_orders_missing_tn_placeholder(string $yyyymm, ?string $orderNo, string $rowKey): string
{
    $orderPart = $orderNo !== null && trim($orderNo) !== ''
        ? preg_replace('/[^A-Za-z0-9]/', '', trim($orderNo)) ?? ''
        : '';
    if ($orderPart === '') {
        $orderPart = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rowKey) ?? 'ROW');
    }

    return 'MISSING' . $yyyymm . $orderPart;
}

function historical_orders_courier_from_tn(string $tn, string $pipeline): string
{
    $upper = strtoupper($tn);
    if (str_starts_with($upper, 'JT') || str_starts_with($upper, 'JNT')) {
        return 'J&T Express';
    }
    if (str_starts_with($upper, 'SPE') || str_starts_with($upper, 'SPX')) {
        return 'SPX';
    }

    return historical_orders_default_courier($pipeline);
}

function historical_orders_default_courier(string $pipeline): string
{
    return match ($pipeline) {
        'shopee_spx' => 'SPX',
        'tiktok' => 'J&T Express',
        'partner' => 'J&T Express',
        default => 'J&T Express',
    };
}

/**
 * Infer source_pipeline from partner iPYX courier label (Ian/Jono/Joven lists mix channels).
 */
function historical_orders_pipeline_from_courier(string $courier, string $fallback): string
{
    $c = strtoupper($courier);
    if (str_contains($c, 'SHOPEE') || str_contains($c, 'SPX')) {
        return 'shopee_spx';
    }
    if (str_contains($c, 'TIKTOK') || str_contains($c, 'TIK TOK')) {
        return 'tiktok';
    }
    if (str_contains($c, 'LAZADA') || str_contains($c, 'FLASH')) {
        return 'partner';
    }
    if (in_array($fallback, HISTORICAL_ORDERS_PIPELINES, true)) {
        return $fallback;
    }

    return 'partner';
}

function historical_orders_normalize_courier_label(string $courier, string $pipeline): string
{
    $c = trim($courier);
    if ($c !== '') {
        return $c;
    }

    return historical_orders_default_courier($pipeline);
}

/**
 * @return ?string ian|jono|joven
 */
function historical_orders_normalize_partner_seller(?string $raw): ?string
{
    return online_orders_normalize_partner_seller($raw);
}

/**
 * @return array{items_raw:string,items_parsed_json:?string,parse_ok:bool,parse_errors:list<string>}
 */
function historical_orders_prepare_items(string $remarks): array
{
    $raw = online_orders_normalize_items_display(trim($remarks));
    if ($raw === '') {
        return [
            'items_raw' => '',
            'items_parsed_json' => null,
            'parse_ok' => false,
            'parse_errors' => ['Remarks / items is empty.'],
        ];
    }

    $parsed = honey_parcel_parse_items_string($raw);
    $errors = $parsed['errors'] ?? [];
    $hasLines = ($parsed['deltas'] ?? []) !== [] || ($parsed['other_items'] ?? []) !== [];
    if ($errors === [] && $hasLines) {
        $json = json_encode(
            ['deltas' => $parsed['deltas'], 'other_items' => $parsed['other_items'] ?? []],
            JSON_UNESCAPED_UNICODE
        );

        return [
            'items_raw' => $raw,
            'items_parsed_json' => is_string($json) ? $json : null,
            'parse_ok' => true,
            'parse_errors' => [],
        ];
    }

    return [
        'items_raw' => $raw,
        'items_parsed_json' => null,
        'parse_ok' => false,
        'parse_errors' => $errors !== [] ? array_values($errors) : ['Could not parse items; will store raw remarks.'],
    ];
}

/**
 * @param array<string, mixed> $existing formatted parcel or DB-ish row
 * @return array<string, mixed>
 */
function historical_orders_existing_snapshot(array $existing): array
{
    return [
        'id' => (int) ($existing['id'] ?? 0),
        'tracking_number' => (string) ($existing['tracking_number'] ?? ''),
        'recipient_name' => (string) ($existing['recipient_name'] ?? ''),
        'order_no' => $existing['order_no'] !== null && $existing['order_no'] !== ''
            ? (string) $existing['order_no'] : null,
        'items_raw' => (string) ($existing['items_raw'] ?? ''),
        'courier' => (string) ($existing['courier'] ?? ''),
        'source_pipeline' => (string) ($existing['source_pipeline'] ?? ''),
        'partner_seller' => isset($existing['partner_seller']) && $existing['partner_seller'] !== ''
            ? (string) $existing['partner_seller'] : null,
        'status' => (string) ($existing['status'] ?? ''),
        'ship_date' => $existing['ship_date'] !== null && $existing['ship_date'] !== ''
            ? (string) $existing['ship_date'] : null,
        'rts_case_id' => isset($existing['rts_case_id']) && $existing['rts_case_id'] !== null
            ? (int) $existing['rts_case_id'] : null,
        'has_packing_photo' => is_array($existing['packing_photo'] ?? null),
        'sensitive' => in_array((string) ($existing['status'] ?? ''), ['rts', 'packing'], true)
            || (isset($existing['rts_case_id']) && $existing['rts_case_id'] !== null)
            || is_array($existing['packing_photo'] ?? null),
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function historical_orders_preview(PDO $pdo, array $input): array
{
    online_parcels_ensure_schema($pdo);

    $token = trim((string) ($input['token'] ?? ''));
    $pipeline = strtolower(trim((string) ($input['pipeline'] ?? 'vip')));
    if (!in_array($pipeline, HISTORICAL_ORDERS_PIPELINES, true)) {
        throw new RuntimeException('Invalid pipeline.');
    }
    $partnerSeller = historical_orders_normalize_partner_seller(
        isset($input['partner_seller']) ? (string) $input['partner_seller'] : null
    );
    if ($pipeline === 'partner' && $partnerSeller === null) {
        throw new RuntimeException('partner_seller required for partner imports (ian, jono, or joven).');
    }
    if ($pipeline !== 'partner') {
        $partnerSeller = null;
    }
    $year = (int) ($input['year'] ?? 0);
    $month = (int) ($input['month'] ?? 0);
    $range = historical_orders_resolve_month($year, $month);
    $sheetName = trim((string) ($input['sheet'] ?? $input['sheet_name'] ?? ''));

    $path = historical_orders_resolve_stash_path($token);
    $data = historical_orders_run_python_parse($path, $sheetName !== '' ? $sheetName : null);
    $format = (string) ($data['format'] ?? '');
    $orders = is_array($data['orders'] ?? null) ? $data['orders'] : [];
    if ($orders === []) {
        throw new RuntimeException(
            'No order rows found. Use Snappy (Tracking number) or partner iPYX (Order No. / Name / Item/s) Excel.'
        );
    }

    $newRows = [];
    $conflicts = [];
    $failed = [];
    $rowIndex = 0;
    $usedTn = [];

    foreach ($orders as $order) {
        if (!is_array($order)) {
            continue;
        }
        $rowIndex++;
        $receiver = trim((string) ($order['recipient_name'] ?? ''));
        $orderNo = trim((string) ($order['order_no'] ?? ''));
        $itemsSrc = trim((string) ($order['items_raw'] ?? ''));
        $tnRaw = (string) ($order['tracking_number'] ?? '');
        $courierRaw = trim((string) ($order['courier'] ?? ''));
        $rowKey = 'r' . $rowIndex;

        if ($receiver === '' && $orderNo === '' && $itemsSrc === '' && trim($tnRaw) === '') {
            continue;
        }

        try {
            $tn = historical_orders_normalize_tn($tnRaw);
            if ($itemsSrc === '') {
                throw new RuntimeException('Product / items is empty.');
            }
            $prepared = historical_orders_prepare_items($itemsSrc);
            if ($prepared['items_raw'] === '') {
                throw new RuntimeException('Items text is empty after normalize.');
            }

            $rowPipeline = $pipeline;
            if ($format === 'partner_ipyx') {
                $rowPipeline = historical_orders_pipeline_from_courier($courierRaw, $pipeline === 'partner' ? 'partner' : $pipeline);
            }

            $trackingMissing = ($tn === '');
            if ($trackingMissing) {
                // Partner iPYX: Order No. is the natural key (no waybill column).
                if ($format === 'partner_ipyx' && $orderNo !== '') {
                    $tn = historical_orders_normalize_tn($orderNo);
                    if ($tn === '') {
                        $tn = historical_orders_missing_tn_placeholder($range['yyyymm'], $orderNo, $rowKey);
                    }
                } else {
                    $tn = historical_orders_missing_tn_placeholder(
                        $range['yyyymm'],
                        $orderNo !== '' ? $orderNo : null,
                        $rowKey
                    );
                }
            }

            // Keep tracking unique within this import batch (duplicate order nos in sheet).
            $baseTn = $tn;
            $suffix = 2;
            while (isset($usedTn[$tn])) {
                $tn = $baseTn . 'D' . $suffix;
                $suffix++;
            }
            $usedTn[$tn] = true;

            if ($format === 'partner_ipyx') {
                $courier = historical_orders_normalize_courier_label($courierRaw, $rowPipeline);
            } elseif (!$trackingMissing) {
                $courier = historical_orders_courier_from_tn($tn, $rowPipeline);
            } else {
                $courier = historical_orders_default_courier($rowPipeline);
            }

            $rowShipDate = trim((string) ($order['ship_date'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rowShipDate)) {
                $rowShipDate = $range['ship_date'];
            }

            $uploaded = [
                'row_key' => $rowKey,
                'tracking_number' => $tn,
                'tracking_missing' => $trackingMissing,
                'original_tracking_number' => $trackingMissing ? null : historical_orders_normalize_tn($tnRaw),
                'recipient_name' => $receiver !== '' ? $receiver : 'Unknown',
                'order_no' => $orderNo !== '' ? $orderNo : null,
                'items_raw' => $prepared['items_raw'],
                'items_parsed_json' => $prepared['items_parsed_json'],
                'parse_ok' => $prepared['parse_ok'],
                'parse_errors' => $prepared['parse_errors'],
                'courier' => $courier,
                'source_pipeline' => $rowPipeline,
                'partner_seller' => $partnerSeller,
                'status' => 'shipped',
                'ship_date' => $rowShipDate,
                'sheet_status' => (string) ($order['sheet_status'] ?? ''),
            ];

            $existing = online_parcels_get_by_tracking($pdo, $tn);
            if ($existing !== null) {
                $snap = historical_orders_existing_snapshot($existing);
                $conflicts[] = [
                    'row_key' => $rowKey,
                    'tracking_number' => $tn,
                    'default_action' => $snap['sensitive'] ? 'keep' : 'use_uploaded',
                    'existing' => $snap,
                    'uploaded' => $uploaded,
                    'diff_fields' => historical_orders_diff_fields($snap, $uploaded),
                ];
            } else {
                $newRows[] = $uploaded;
            }
        } catch (Throwable $e) {
            $failed[] = [
                'row_key' => $rowKey,
                'parcel_ref' => $orderNo !== '' ? $orderNo : $tnRaw,
                'receiver' => $receiver,
                'remarks' => mb_substr($itemsSrc, 0, 200),
                'error' => $e->getMessage(),
            ];
        }
    }

    $previewToken = bin2hex(random_bytes(16));
    ims_session_start();
    if (!isset($_SESSION['historical_orders_preview']) || !is_array($_SESSION['historical_orders_preview'])) {
        $_SESSION['historical_orders_preview'] = [];
    }
    $_SESSION['historical_orders_preview'][$previewToken] = [
        'expires' => time() + 3600,
        'ship_date' => $range['ship_date'],
        'yyyymm' => $range['yyyymm'],
        'month_label' => $range['label'],
        'pipeline' => $pipeline,
        'partner_seller' => $partnerSeller,
        'format' => $format,
        'sheet' => (string) ($data['sheet'] ?? ''),
        'new' => $newRows,
        'conflicts' => $conflicts,
        'failed' => $failed,
    ];

    // Prune expired previews
    foreach ($_SESSION['historical_orders_preview'] as $pt => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            unset($_SESSION['historical_orders_preview'][$pt]);
        }
    }

    return [
        'preview_token' => $previewToken,
        'month_label' => $range['label'],
        'ship_date' => $range['ship_date'],
        'pipeline' => $pipeline,
        'partner_seller' => $partnerSeller,
        'format' => $format,
        'sheet' => (string) ($data['sheet'] ?? ''),
        'available_sheets' => is_array($data['available_sheets'] ?? null) ? $data['available_sheets'] : [],
        'counts' => [
            'new' => count($newRows),
            'conflicts' => count($conflicts),
            'failed' => count($failed),
            'total_excel_rows' => count($orders),
        ],
        'new' => $newRows,
        'conflicts' => $conflicts,
        'failed' => $failed,
    ];
}

/**
 * @param array<string, mixed> $existing
 * @param array<string, mixed> $uploaded
 * @return list<string>
 */
function historical_orders_diff_fields(array $existing, array $uploaded): array
{
    $fields = ['recipient_name', 'order_no', 'items_raw', 'courier', 'source_pipeline', 'status', 'ship_date'];
    $diff = [];
    foreach ($fields as $f) {
        $a = (string) ($existing[$f] ?? '');
        $b = (string) ($uploaded[$f] ?? '');
        if ($a !== $b) {
            $diff[] = $f;
        }
    }

    return $diff;
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function historical_orders_commit(PDO $pdo, array $input): array
{
    online_parcels_ensure_schema($pdo);

    $previewToken = preg_replace('/[^a-f0-9]/', '', strtolower(trim((string) ($input['preview_token'] ?? '')))) ?? '';
    if ($previewToken === '') {
        throw new RuntimeException('preview_token required.');
    }

    ims_session_start();
    $preview = $_SESSION['historical_orders_preview'][$previewToken] ?? null;
    if (!is_array($preview) || (int) ($preview['expires'] ?? 0) < time()) {
        throw new RuntimeException('Preview expired. Run preview again.');
    }

    $decisionsRaw = $input['decisions'] ?? [];
    if (!is_array($decisionsRaw)) {
        $decisionsRaw = [];
    }
    $decisionMap = [];
    foreach ($decisionsRaw as $d) {
        if (!is_array($d)) {
            continue;
        }
        $key = trim((string) ($d['row_key'] ?? ''));
        $action = strtolower(trim((string) ($d['action'] ?? '')));
        if ($key === '' || !in_array($action, ['keep', 'use_uploaded'], true)) {
            continue;
        }
        $decisionMap[$key] = $action;
    }

    $importNew = !isset($input['import_new']) || (bool) $input['import_new'];
    $shipDate = (string) ($preview['ship_date'] ?? '');

    $inserted = 0;
    $updated = 0;
    $kept = 0;
    $errors = [];

    $pdo->beginTransaction();
    try {
        if ($importNew) {
            foreach ($preview['new'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                try {
                    $rowShip = trim((string) ($row['ship_date'] ?? ''));
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rowShip)) {
                        $rowShip = $shipDate;
                    }
                    historical_orders_insert_shipped($pdo, $row, $rowShip . ' 12:00:00');
                    $inserted++;
                } catch (Throwable $e) {
                    $errors[] = [
                        'row_key' => (string) ($row['row_key'] ?? ''),
                        'tracking_number' => (string) ($row['tracking_number'] ?? ''),
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        foreach ($preview['conflicts'] ?? [] as $conflict) {
            if (!is_array($conflict)) {
                continue;
            }
            $rowKey = (string) ($conflict['row_key'] ?? '');
            $default = (string) ($conflict['default_action'] ?? 'keep');
            $action = $decisionMap[$rowKey] ?? $default;
            if ($action === 'keep') {
                $kept++;
                continue;
            }
            $uploaded = is_array($conflict['uploaded'] ?? null) ? $conflict['uploaded'] : null;
            $existing = is_array($conflict['existing'] ?? null) ? $conflict['existing'] : null;
            if ($uploaded === null || $existing === null) {
                continue;
            }
            try {
                $rowShip = trim((string) ($uploaded['ship_date'] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rowShip)) {
                    $rowShip = $shipDate;
                }
                historical_orders_overwrite_shipped($pdo, (int) ($existing['id'] ?? 0), $uploaded, $rowShip . ' 12:00:00');
                $updated++;
            } catch (Throwable $e) {
                $errors[] = [
                    'row_key' => $rowKey,
                    'tracking_number' => (string) ($uploaded['tracking_number'] ?? ''),
                    'error' => $e->getMessage(),
                ];
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    unset($_SESSION['historical_orders_preview'][$previewToken]);

    return [
        'month_label' => (string) ($preview['month_label'] ?? ''),
        'ship_date' => $shipDate,
        'pipeline' => (string) ($preview['pipeline'] ?? ''),
        'partner_seller' => $preview['partner_seller'] ?? null,
        'inserted' => $inserted,
        'updated' => $updated,
        'kept' => $kept,
        'failed_preview' => count($preview['failed'] ?? []),
        'errors' => $errors,
    ];
}

/**
 * CLI / offline import: parse workbook path and insert all new rows (keep conflicts unchanged).
 *
 * @param array{year:int,month:int,pipeline?:string,partner_seller?:string,sheet?:string} $opts
 * @return array<string, mixed>
 */
function historical_orders_import_file(PDO $pdo, string $workbookPath, array $opts): array
{
    if (!is_file($workbookPath)) {
        throw new RuntimeException('Workbook not found: ' . $workbookPath);
    }

    $pipeline = strtolower(trim((string) ($opts['pipeline'] ?? 'partner')));
    $partnerSeller = historical_orders_normalize_partner_seller(
        isset($opts['partner_seller']) ? (string) $opts['partner_seller'] : null
    );
    $year = (int) ($opts['year'] ?? 0);
    $month = (int) ($opts['month'] ?? 0);
    $sheet = trim((string) ($opts['sheet'] ?? ''));
    $range = historical_orders_resolve_month($year, $month);

    // Fake a stash token via session so preview/commit path stays one source of truth.
    ims_session_start();
    $token = bin2hex(random_bytes(16));
    if (!isset($_SESSION['historical_orders_stash']) || !is_array($_SESSION['historical_orders_stash'])) {
        $_SESSION['historical_orders_stash'] = [];
    }
    $_SESSION['historical_orders_stash'][$token] = [
        'path' => $workbookPath,
        'original_name' => basename($workbookPath),
        'expires' => time() + 3600,
    ];

    $preview = historical_orders_preview($pdo, [
        'token' => $token,
        'pipeline' => $pipeline,
        'partner_seller' => $partnerSeller,
        'year' => $year,
        'month' => $month,
        'sheet' => $sheet,
    ]);

    // Default: keep conflicts (don't overwrite live/existing)
    $decisions = [];
    foreach ($preview['conflicts'] ?? [] as $c) {
        if (!is_array($c)) {
            continue;
        }
        $decisions[] = [
            'row_key' => (string) ($c['row_key'] ?? ''),
            'action' => 'keep',
        ];
    }

    $commit = historical_orders_commit($pdo, [
        'preview_token' => (string) ($preview['preview_token'] ?? ''),
        'import_new' => true,
        'decisions' => $decisions,
    ]);

    unset($_SESSION['historical_orders_stash'][$token]);

    return [
        'preview_counts' => $preview['counts'] ?? [],
        'format' => $preview['format'] ?? null,
        'sheet' => $preview['sheet'] ?? null,
        'month_label' => $range['label'],
        'ship_date' => $range['ship_date'],
        'partner_seller' => $partnerSeller,
        'commit' => $commit,
    ];
}

/**
 * @param array<string, mixed> $row
 */
function historical_orders_insert_shipped(PDO $pdo, array $row, string $createdAt): void
{
    $tn = trim((string) ($row['tracking_number'] ?? ''));
    if ($tn === '') {
        throw new RuntimeException('Missing tracking number.');
    }
    if (online_parcels_get_by_tracking($pdo, $tn) !== null) {
        throw new RuntimeException('Tracking already exists: ' . $tn);
    }

    $st = $pdo->prepare(
        'INSERT INTO online_order_parcels (
            csr_batch_id, csr_line_id, tracking_number, items_raw, items_parsed_json,
            recipient_name, courier, order_no, source_pipeline, partner_seller,
            status, ship_date, created_at
         ) VALUES (NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, "shipped", ?, ?)'
    );
    $partnerSeller = isset($row['partner_seller']) && $row['partner_seller'] !== null && $row['partner_seller'] !== ''
        ? (string) $row['partner_seller']
        : null;
    $st->execute([
        $tn,
        (string) ($row['items_raw'] ?? ''),
        $row['items_parsed_json'] ?? null,
        (string) ($row['recipient_name'] ?? ''),
        (string) ($row['courier'] ?? 'J&T Express'),
        isset($row['order_no']) && $row['order_no'] !== null && $row['order_no'] !== ''
            ? (string) $row['order_no'] : null,
        (string) ($row['source_pipeline'] ?? 'vip'),
        $partnerSeller,
        (string) ($row['ship_date'] ?? ''),
        $createdAt,
    ]);
}

/**
 * @param array<string, mixed> $row
 */
function historical_orders_overwrite_shipped(PDO $pdo, int $parcelId, array $row, string $createdAt): void
{
    if ($parcelId < 1) {
        throw new RuntimeException('Invalid parcel id for overwrite.');
    }

    $st = $pdo->prepare(
        'UPDATE online_order_parcels
         SET recipient_name = ?,
             items_raw = ?,
             items_parsed_json = ?,
             order_no = ?,
             courier = ?,
             source_pipeline = ?,
             partner_seller = ?,
             status = "shipped",
             ship_date = ?,
             created_at = ?
         WHERE id = ?'
    );
    $partnerSeller = isset($row['partner_seller']) && $row['partner_seller'] !== null && $row['partner_seller'] !== ''
        ? (string) $row['partner_seller']
        : null;
    $st->execute([
        (string) ($row['recipient_name'] ?? ''),
        (string) ($row['items_raw'] ?? ''),
        $row['items_parsed_json'] ?? null,
        isset($row['order_no']) && $row['order_no'] !== null && $row['order_no'] !== ''
            ? (string) $row['order_no'] : null,
        (string) ($row['courier'] ?? 'J&T Express'),
        (string) ($row['source_pipeline'] ?? 'vip'),
        $partnerSeller,
        (string) ($row['ship_date'] ?? ''),
        $createdAt,
        $parcelId,
    ]);
}
