<?php

declare(strict_types=1);

/**
 * Historical VIP (and other pipeline) ORDER LIST Excel → online_order_parcels backfill.
 * Two-step: preview (with TN conflicts) → commit (insert new / keep or overwrite conflicts).
 */

require_once __DIR__ . '/online_order_parcels.php';
require_once __DIR__ . '/online_orders_generator.php';
require_once __DIR__ . '/order_remarks.php';
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
 * VIP ORDER LIST has no courier waybill — use Parcel Name as plain TN
 * (no "VIP" prefix, no dashes), e.g. 130441.
 */
function historical_orders_synthetic_tn(string $parcelName): string
{
    $ref = preg_replace('/[^A-Za-z0-9]/', '', $parcelName) ?? '';
    $ref = trim($ref);
    if ($ref === '') {
        throw new RuntimeException('Parcel Name is empty; cannot build tracking number.');
    }

    return $ref;
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
    $year = (int) ($input['year'] ?? 0);
    $month = (int) ($input['month'] ?? 0);
    $range = historical_orders_resolve_month($year, $month);
    $profile = trim((string) ($input['profile'] ?? 'vip_order_list'));
    if ($profile === '') {
        $profile = 'vip_order_list';
    }

    $path = historical_orders_resolve_stash_path($token);
    $data = order_remarks_run_python('detect', $path, $profile);
    $orders = is_array($data['orders'] ?? null) ? $data['orders'] : [];
    if ($orders === []) {
        throw new RuntimeException('No order rows found in this Excel. Use a VIP J&T ORDER LIST file.');
    }

    $courier = historical_orders_default_courier($pipeline);
    $newRows = [];
    $conflicts = [];
    $failed = [];
    $rowIndex = 0;

    foreach ($orders as $order) {
        if (!is_array($order)) {
            continue;
        }
        $rowIndex++;
        $receiver = trim((string) ($order['receiver'] ?? ''));
        $parcelRef = trim((string) ($order['parcel_ref'] ?? $order['ref'] ?? ''));
        $remarks = trim((string) ($order['remarks'] ?? ''));
        $rowKey = 'r' . $rowIndex;

        if ($receiver === '' && $parcelRef === '' && $remarks === '') {
            continue;
        }

        try {
            if ($parcelRef === '') {
                throw new RuntimeException('Parcel Name is missing.');
            }
            if ($remarks === '') {
                throw new RuntimeException('Remarks is empty.');
            }
            $tn = historical_orders_synthetic_tn($parcelRef);
            $prepared = historical_orders_prepare_items($remarks);
            if ($prepared['items_raw'] === '') {
                throw new RuntimeException('Items text is empty after normalize.');
            }

            $uploaded = [
                'row_key' => $rowKey,
                'tracking_number' => $tn,
                'recipient_name' => $receiver !== '' ? $receiver : 'Unknown',
                'order_no' => $parcelRef,
                'items_raw' => $prepared['items_raw'],
                'items_parsed_json' => $prepared['items_parsed_json'],
                'parse_ok' => $prepared['parse_ok'],
                'parse_errors' => $prepared['parse_errors'],
                'courier' => $courier,
                'source_pipeline' => $pipeline,
                'partner_seller' => null,
                'status' => 'shipped',
                'ship_date' => $range['ship_date'],
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
                'parcel_ref' => $parcelRef,
                'receiver' => $receiver,
                'remarks' => mb_substr($remarks, 0, 200),
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
    $createdAt = $shipDate . ' 12:00:00';

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
                    historical_orders_insert_shipped($pdo, $row, $createdAt);
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
                historical_orders_overwrite_shipped($pdo, (int) ($existing['id'] ?? 0), $uploaded, $createdAt);
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
        'inserted' => $inserted,
        'updated' => $updated,
        'kept' => $kept,
        'failed_preview' => count($preview['failed'] ?? []),
        'errors' => $errors,
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
         ) VALUES (NULL, NULL, ?, ?, ?, ?, ?, ?, ?, NULL, "shipped", ?, ?)'
    );
    $st->execute([
        $tn,
        (string) ($row['items_raw'] ?? ''),
        $row['items_parsed_json'] ?? null,
        (string) ($row['recipient_name'] ?? ''),
        (string) ($row['courier'] ?? 'J&T Express'),
        isset($row['order_no']) && $row['order_no'] !== null && $row['order_no'] !== ''
            ? (string) $row['order_no'] : null,
        (string) ($row['source_pipeline'] ?? 'vip'),
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
             partner_seller = NULL,
             status = "shipped",
             ship_date = ?,
             created_at = ?
         WHERE id = ?'
    );
    $st->execute([
        (string) ($row['recipient_name'] ?? ''),
        (string) ($row['items_raw'] ?? ''),
        $row['items_parsed_json'] ?? null,
        isset($row['order_no']) && $row['order_no'] !== null && $row['order_no'] !== ''
            ? (string) $row['order_no'] : null,
        (string) ($row['courier'] ?? 'J&T Express'),
        (string) ($row['source_pipeline'] ?? 'vip'),
        (string) ($row['ship_date'] ?? ''),
        $createdAt,
        $parcelId,
    ]);
}
