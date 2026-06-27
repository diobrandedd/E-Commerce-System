<?php

declare(strict_types=1);

/**
 * CSR Online Orders Generator — batch orchestration, line storage, master iPYX merge.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/waybill_stamper.php';
require_once __DIR__ . '/order_remarks.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const ONLINE_ORDERS_PIPELINES = ['partner', 'vip', 'shopee_spx', 'tiktok', 'unified'];
const ONLINE_ORDERS_PARTNER_SELLERS = ['ian', 'jono', 'joven'];

function online_orders_normalize_pipeline(string $pipeline): string
{
    $p = strtolower(trim($pipeline));
    if (!in_array($p, ONLINE_ORDERS_PIPELINES, true)) {
        return 'unified';
    }

    return $p;
}

function online_orders_normalize_partner_seller(?string $seller): ?string
{
    $s = strtolower(trim((string) $seller));
    if ($s === '') {
        return null;
    }
    if (!in_array($s, ONLINE_ORDERS_PARTNER_SELLERS, true)) {
        throw new RuntimeException('Invalid partner_seller (use ian, jono, or joven).');
    }

    return $s;
}

function online_orders_require_partner_seller_for_pipeline(string $pipeline, ?string $partnerSeller): ?string
{
    $pipeline = online_orders_normalize_pipeline($pipeline);
    $seller = online_orders_normalize_partner_seller($partnerSeller);
    if ($pipeline === 'partner' && $seller === null) {
        throw new RuntimeException('partner_seller required for Partner orders (ian, jono, or joven).');
    }
    if ($pipeline !== 'partner') {
        return null;
    }

    return $seller;
}

/** 1-based column index + row → Excel coordinate (e.g. B4). PhpSpreadsheet 2.x removed *ByColumnAndRow helpers. */
function online_orders_coord(int $columnIndex1Based, int $row1Based): string
{
    return Coordinate::stringFromColumnIndex($columnIndex1Based) . $row1Based;
}

function online_orders_storage_root(): string
{
    return rts_storage_csr_tool_root(
        RTS_STORAGE_BUCKET_ONLINE_ORDERS,
        'online-orders',
        ['outputs', 'work'],
    );
}

function online_orders_user_can_access(array $user): bool
{
    return waybill_stamper_user_can_access($user);
}

function online_orders_require_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        throw new RuntimeException('Not authenticated.');
    }
    if (!online_orders_user_can_access($user)) {
        throw new RuntimeException('Forbidden.');
    }
    return $user;
}

function online_orders_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->query('SELECT 1 FROM csr_online_batches LIMIT 1');
    $done = true;
}

/**
 * @return list<array{order_no:?string,name:string,items:string,courier:string,tracking_number:string}>
 */
function online_orders_parse_ipyx_excel_paths(array $excelPaths): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer autoload missing (PhpSpreadsheet).');
    }
    require_once $autoload;

    $tnNames = ['tracking number', 'tracking no', 'tracking no.', 'tracking', 'waybill'];
    $itemNames = ['item/s', 'items', 'item', 'product', 'products', 'description'];
    $nameNames = ['name', 'buyer name', 'customer name', 'recipient'];
    $orderNames = ['order no.', 'order no', 'order number', 'order id', 'order'];
    $courierNames = ['courier', 'carrier', 'logistics'];
    $skipSheets = ['archive', 'template', 'readme', 'instructions'];

    $rows = [];
    $seenTn = [];

    foreach ($excelPaths as $path) {
        if (!is_file($path)) {
            continue;
        }
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            throw new RuntimeException('Cannot open Excel: ' . basename($path));
        }

        // iPYX partner workbook: today's orders live on the first sheet only.
        $sheet = $spreadsheet->getSheet(0);
        $sheetName = (string) $sheet->getTitle();
        $lower = strtolower($sheetName);
        $skip = false;
        foreach ($skipSheets as $s) {
            if (str_contains($lower, $s)) {
                $skip = true;
                break;
            }
        }

        if (!$skip) {
            $highestRow = (int) $sheet->getHighestRow();
            $colIndex = Coordinate::columnIndexFromString((string) $sheet->getHighestColumn());

            $headerIdx = null;
            $colMap = [];
            for ($r = 1; $r <= min($highestRow, 30); $r++) {
                $cells = [];
                for ($c = 1; $c <= $colIndex; $c++) {
                    $v = $sheet->getCell(online_orders_coord($c, $r))->getValue();
                    $cells[] = strtolower(trim((string) ($v ?? '')));
                }
                $found = [];
                foreach ($cells as $idx => $cell) {
                    if (in_array($cell, $tnNames, true)) {
                        $found['tn'] = $idx;
                    }
                    if (in_array($cell, $itemNames, true)) {
                        $found['items'] = $idx;
                    }
                    if (in_array($cell, $nameNames, true)) {
                        $found['name'] = $idx;
                    }
                    if (in_array($cell, $orderNames, true)) {
                        $found['order'] = $idx;
                    }
                    if (in_array($cell, $courierNames, true)) {
                        $found['courier'] = $idx;
                    }
                }
                if (isset($found['items']) && (isset($found['tn']) || isset($found['name']))) {
                    $headerIdx = $r;
                    $colMap = $found;
                    break;
                }
            }

            if ($headerIdx !== null) {
                for ($r = $headerIdx + 1; $r <= $highestRow; $r++) {
                    $gcell = static function (string $key) use ($sheet, $colMap, $r): string {
                        $idx = $colMap[$key] ?? null;
                        if ($idx === null) {
                            return '';
                        }
                        $col = $idx + 1;
                        $v = $sheet->getCell(online_orders_coord($col, $r))->getValue();
                        if ($v === null) {
                            return '';
                        }
                        if (is_float($v) && $v == (int) $v) {
                            return (string) (int) $v;
                        }
                        return trim((string) $v);
                    };

                    $tn = $gcell('tn');
                    if ($tn === '' || in_array(strtolower($tn), $tnNames, true)) {
                        continue;
                    }
                    $key = online_orders_normalize_tracking($tn);
                    if ($key === '' || isset($seenTn[$key])) {
                        continue;
                    }
                    $seenTn[$key] = true;

                    $rows[] = [
                        'order_no' => $gcell('order') !== '' ? $gcell('order') : null,
                        'name' => $gcell('name'),
                        'items' => $gcell('items'),
                        'courier' => $gcell('courier'),
                        'tracking_number' => $tn,
                    ];
                }
            }
        }

        $spreadsheet->disconnectWorksheets();
    }

    return $rows;
}

function online_orders_batch_start(PDO $pdo, int $userId): array
{
    online_orders_ensure_schema($pdo);

    $st = $pdo->prepare(
        'SELECT id FROM csr_online_batches WHERE user_id = ? AND status = ? LIMIT 1'
    );
    $st->execute([$userId, 'active']);
    $existing = $st->fetchColumn();
    if ($existing !== false) {
        throw new RuntimeException('You already have an active batch. Finish or cancel it first.');
    }

    $ins = $pdo->prepare('INSERT INTO csr_online_batches (user_id, status) VALUES (?, ?)');
    $ins->execute([$userId, 'active']);
    $batchId = (int) $pdo->lastInsertId();

    return online_orders_batch_get($pdo, $userId, $batchId);
}

/**
 * @return array<string, mixed>|null
 */
function online_orders_batch_get(PDO $pdo, int $userId, ?int $batchId = null): ?array
{
    online_orders_ensure_schema($pdo);

    if ($batchId !== null && $batchId > 0) {
        $st = $pdo->prepare(
            'SELECT * FROM csr_online_batches WHERE id = ? AND user_id = ? LIMIT 1'
        );
        $st->execute([$batchId, $userId]);
    } else {
        $st = $pdo->prepare(
            'SELECT * FROM csr_online_batches WHERE user_id = ? AND status = ? ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$userId, 'active']);
    }
    $batch = $st->fetch(PDO::FETCH_ASSOC);
    if (!$batch) {
        return null;
    }

    $bid = (int) $batch['id'];
    $runsSt = $pdo->prepare(
        'SELECT id, pipeline, status, meta_json, created_at FROM csr_online_batch_runs WHERE batch_id = ? ORDER BY id ASC'
    );
    $runsSt->execute([$bid]);
    $runs = [];
    while ($row = $runsSt->fetch(PDO::FETCH_ASSOC)) {
        $meta = $row['meta_json'];
        $runs[] = [
            'id' => (int) $row['id'],
            'pipeline' => (string) $row['pipeline'],
            'status' => (string) $row['status'],
            'meta' => is_string($meta) ? json_decode($meta, true) : $meta,
            'created_at' => (string) $row['created_at'],
        ];
    }

    $cntSt = $pdo->prepare('SELECT COUNT(*) FROM csr_online_order_lines WHERE batch_id = ?');
    $cntSt->execute([$bid]);
    $lineCount = (int) $cntSt->fetchColumn();

    return [
        'id' => $bid,
        'status' => (string) $batch['status'],
        'created_at' => (string) $batch['created_at'],
        'completed_at' => $batch['completed_at'] !== null ? (string) $batch['completed_at'] : null,
        'line_count' => $lineCount,
        'runs' => $runs,
        'master_download_token' => $batch['master_file_token'] !== null ? (string) $batch['master_file_token'] : null,
        'master_filename' => $batch['master_filename'] !== null ? (string) $batch['master_filename'] : null,
    ];
}

function online_orders_batch_cancel(PDO $pdo, int $userId, int $batchId): void
{
    online_orders_ensure_schema($pdo);
    $st = $pdo->prepare(
        'UPDATE csr_online_batches SET status = ? WHERE id = ? AND user_id = ? AND status = ?'
    );
    $st->execute(['cancelled', $batchId, $userId, 'active']);
    if ($st->rowCount() < 1) {
        throw new RuntimeException('Active batch not found.');
    }
}

function online_orders_assert_active_batch(PDO $pdo, int $userId, int $batchId): void
{
    $st = $pdo->prepare(
        'SELECT id FROM csr_online_batches WHERE id = ? AND user_id = ? AND status = ? LIMIT 1'
    );
    $st->execute([$batchId, $userId, 'active']);
    if (!$st->fetchColumn()) {
        throw new RuntimeException('Batch not found or not active.');
    }
}

function online_orders_run_start(PDO $pdo, int $batchId, string $pipeline): int
{
    $ins = $pdo->prepare(
        'INSERT INTO csr_online_batch_runs (batch_id, pipeline, status) VALUES (?, ?, ?)'
    );
    $ins->execute([$batchId, $pipeline, 'started']);
    return (int) $pdo->lastInsertId();
}

function online_orders_run_complete(PDO $pdo, int $runId, array $meta): void
{
    $st = $pdo->prepare(
        'UPDATE csr_online_batch_runs SET status = ?, meta_json = ? WHERE id = ?'
    );
    $st->execute(['completed', json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $runId]);
}

class OnlineOrdersDuplicateTrackingException extends RuntimeException
{
    /** @param list<array<string, mixed>> $duplicates */
    public function __construct(public array $duplicates, ?string $message = null)
    {
        parent::__construct($message ?? 'One or more tracking numbers already exist.');
    }
}

function online_orders_normalize_tracking(string $raw): string
{
    require_once __DIR__ . '/qr_decode.php';
    $t = rts_normalize_tracking_from_qr($raw);
    if ($t === '') {
        return '';
    }

    $t = strtoupper(preg_replace('/\s+/', '', $t));

    return online_orders_ocr_confusables_for_match($t);
}

/**
 * OCR fixes for waybill ↔ Excel matching (Flash/Lazada marketplace P-IDs: O vs 0).
 * Never apply to Shopee express prefixes (PH, SPEPH, SPX) — those legitimately end in letter O.
 */
function online_orders_ocr_confusables_for_match(string $tn): string
{
    if (preg_match('/^(PH|SPEPH|SPX)/', $tn)) {
        return $tn;
    }
    if (preg_match('/^P[A-Z0-9]{11,17}$/', $tn) === 1 && str_contains($tn, 'O')) {
        return str_replace('O', '0', $tn);
    }

    return $tn;
}

/**
 * @return array<string, array<string, mixed>>
 */
function online_orders_existing_tracking_map(PDO $pdo): array
{
    require_once __DIR__ . '/online_order_parcels.php';

    online_parcels_ensure_schema($pdo);
    $map = [];

    $st = $pdo->query(
        'SELECT * FROM online_order_parcels WHERE tracking_number IS NOT NULL AND tracking_number != ""'
    );
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = online_orders_normalize_tracking((string) $row['tracking_number']);
        if ($key === '') {
            continue;
        }
        $map[$key] = [
            'source' => 'parcel',
            'parcel_id' => (int) $row['id'],
            'batch_id' => $row['csr_batch_id'] !== null ? (int) $row['csr_batch_id'] : null,
            'recipient_name' => (string) ($row['recipient_name'] ?? ''),
            'items' => (string) ($row['items_raw'] ?? ''),
            'courier' => (string) ($row['courier'] ?? ''),
            'source_pipeline' => (string) ($row['source_pipeline'] ?? ''),
            'order_no' => $row['order_no'] !== null ? (string) $row['order_no'] : null,
            'parcel_status' => (string) ($row['status'] ?? ''),
            'ship_date' => $row['ship_date'] !== null ? (string) $row['ship_date'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    $st = $pdo->query(
        'SELECT l.id AS line_id, l.batch_id, l.recipient_name, l.items, l.courier, l.tracking_number,
                l.source_pipeline, l.order_no, l.created_at, b.status AS batch_status
         FROM csr_online_order_lines l
         INNER JOIN csr_online_batches b ON b.id = l.batch_id
         WHERE b.status IN ("active", "processed")
         AND l.tracking_number IS NOT NULL AND l.tracking_number != ""'
    );
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = online_orders_normalize_tracking((string) $row['tracking_number']);
        if ($key === '' || isset($map[$key])) {
            continue;
        }
        $map[$key] = [
            'source' => 'batch_line',
            'line_id' => (int) $row['line_id'],
            'batch_id' => (int) $row['batch_id'],
            'batch_status' => (string) ($row['batch_status'] ?? ''),
            'recipient_name' => (string) ($row['recipient_name'] ?? ''),
            'items' => (string) ($row['items'] ?? ''),
            'courier' => (string) ($row['courier'] ?? ''),
            'source_pipeline' => (string) ($row['source_pipeline'] ?? ''),
            'order_no' => $row['order_no'] !== null ? (string) $row['order_no'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    return $map;
}

/**
 * @param list<array{order_no?:?string,name?:string,items:string,courier?:string,tracking_number:string}> $lines
 * @return list<array<string, mixed>>
 */
function online_orders_validate_tracking_duplicates(PDO $pdo, string $pipeline, array $lines): array
{
    $map = online_orders_existing_tracking_map($pdo);
    $conflicts = [];
    $incomingSeen = [];

    foreach ($lines as $line) {
        $rawTn = trim((string) ($line['tracking_number'] ?? ''));
        $items = trim((string) ($line['items'] ?? ''));
        if ($rawTn === '' || $items === '') {
            continue;
        }
        $key = online_orders_normalize_tracking($rawTn);
        if ($key === '') {
            continue;
        }

        $attempted = [
            'recipient_name' => trim((string) ($line['name'] ?? '')),
            'items' => $items,
            'courier' => trim((string) ($line['courier'] ?? '')),
            'source_pipeline' => $pipeline,
            'order_no' => isset($line['order_no']) && $line['order_no'] !== '' && $line['order_no'] !== null
                ? (string) $line['order_no'] : null,
        ];

        if (isset($incomingSeen[$key])) {
            $conflicts[] = [
                'tracking_number' => $key,
                'tracking_raw' => $rawTn,
                'attempted' => $attempted,
                'existing' => array_merge($incomingSeen[$key], ['source' => 'incoming_upload']),
            ];
            continue;
        }
        $incomingSeen[$key] = $attempted;

        if (isset($map[$key])) {
            $conflicts[] = [
                'tracking_number' => $key,
                'tracking_raw' => $rawTn,
                'attempted' => $attempted,
                'existing' => $map[$key],
            ];
        }
    }

    return $conflicts;
}

/**
 * @param list<array{order_no?:?string,name?:string,items:string,courier?:string,tracking_number:string}> $lines
 */
function online_orders_assert_no_duplicate_tracking(PDO $pdo, string $pipeline, array $lines): void
{
    $conflicts = online_orders_validate_tracking_duplicates($pdo, $pipeline, $lines);
    if ($conflicts !== []) {
        throw new OnlineOrdersDuplicateTrackingException($conflicts);
    }
}

/**
 * @param list<array{order_no?:?string,name?:string,items:string,courier?:string,tracking_number:string,partner_seller?:?string}> $lines
 */
function online_orders_lines_append(
    PDO $pdo,
    int $batchId,
    int $runId,
    string $pipeline,
    array $lines,
    ?string $partnerSeller = null
): int
{
    online_orders_assert_no_duplicate_tracking($pdo, $pipeline, $lines);

    $ins = $pdo->prepare(
        'INSERT INTO csr_online_order_lines
            (batch_id, run_id, order_no, recipient_name, items, courier, tracking_number, source_pipeline, partner_seller)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $count = 0;
    foreach ($lines as $line) {
        $rawTn = trim((string) ($line['tracking_number'] ?? ''));
        $items = trim((string) ($line['items'] ?? ''));
        if ($rawTn === '' || $items === '') {
            continue;
        }
        $tn = online_orders_normalize_tracking($rawTn);
        if ($tn === '') {
            $tn = $rawTn;
        }
        $lineSeller = isset($line['partner_seller']) && $line['partner_seller'] !== ''
            ? online_orders_normalize_partner_seller((string) $line['partner_seller'])
            : $partnerSeller;
        $ins->execute([
            $batchId,
            $runId > 0 ? $runId : null,
            isset($line['order_no']) && $line['order_no'] !== '' && $line['order_no'] !== null
                ? (string) $line['order_no'] : null,
            trim((string) ($line['name'] ?? '')),
            online_orders_normalize_items_display($items),
            trim((string) ($line['courier'] ?? '')),
            $tn,
            $pipeline,
            $lineSeller,
        ]);
        $count++;
    }
    return $count;
}

/**
 * Normalize promo shorthand in item text before DB / Excel (e.g. B1T2 350ml → 3× 350ml).
 */
function online_orders_normalize_items_display(string $raw): string
{
    $text = trim($raw);
    if ($text === '') {
        return '';
    }
    if (preg_match('/\bB(\d+)T(\d+)\b/i', $text, $m)) {
        $buy = max(1, (int) $m[1]);
        $bonus = max(0, (int) $m[2]);
        $total = $buy + $bonus;
        $rest = trim(preg_replace('/\bB\d+T\d+\b/i', '', $text));
        $size = $rest !== '' ? ' ' . $rest : '';
        return $total . '×' . $size;
    }

    return $text;
}

/**
 * @param list<array{order_no?:?string,name?:string,items:string,courier?:string,tracking_number:string}> $lines
 * @return list<array{order_no:?string,name:string,items:string,courier:string,tracking_number:string}>
 */
function online_orders_normalize_line_payloads(array $lines): array
{
    $out = [];
    foreach ($lines as $line) {
        $tn = trim((string) ($line['tracking_number'] ?? ''));
        $items = online_orders_normalize_items_display((string) ($line['items'] ?? ''));
        if ($tn === '' || $items === '') {
            continue;
        }
        $out[] = [
            'order_no' => isset($line['order_no']) && $line['order_no'] !== '' && $line['order_no'] !== null
                ? (string) $line['order_no'] : null,
            'name' => trim((string) ($line['name'] ?? '')),
            'items' => $items,
            'courier' => trim((string) ($line['courier'] ?? '')),
            'tracking_number' => $tn,
        ];
    }

    return $out;
}

/**
 * @param list<string>|string $excelTokens
 * @return array{excel_token: string, row_count: int, rows: list<array<string, mixed>>}
 */
function online_orders_tool_excel_register(string|array $excelTokens): array
{
    $tokens = is_array($excelTokens) ? array_values($excelTokens) : [$excelTokens];
    $tokens = array_filter(array_map(static fn ($t) => trim((string) $t), $tokens));
    if ($tokens === []) {
        throw new RuntimeException('excel_token required.');
    }

    $paths = waybill_stamper_resolve_stash_tokens($tokens, 'excel');
    $parsed = online_orders_parse_ipyx_excel_paths($paths);
    if ($parsed === []) {
        throw new RuntimeException('No order rows found in Excel. Use the iPYX / partner column layout.');
    }

    $rows = [];
    foreach ($parsed as $row) {
        $rows[] = [
            'order_no' => $row['order_no'],
            'name' => $row['name'],
            'items' => online_orders_normalize_items_display($row['items']),
            'courier' => $row['courier'],
            'tracking_number' => $row['tracking_number'],
        ];
    }

    return [
        'excel_token' => $tokens[0],
        'excel_tokens' => $tokens,
        'row_count' => count($rows),
        'rows' => $rows,
    ];
}

/**
 * @param list<array{order_no?:?string,tracking:string,name?:string,items:string,courier?:string}> $rows
 * @return array{excel_token: string, row_count: int, rows: list<array<string, mixed>>}
 */
function online_orders_tool_spx_prepare(array $rows): array
{
    $normalized = [];
    foreach ($rows as $row) {
        $tn = trim((string) ($row['tracking'] ?? $row['tracking_number'] ?? ''));
        $items = online_orders_normalize_items_display(trim((string) ($row['items'] ?? '')));
        if ($tn === '' || $items === '') {
            continue;
        }
        $orderNo = isset($row['order_no']) && $row['order_no'] !== '' && $row['order_no'] !== null
            ? trim((string) $row['order_no']) : null;
        $normalized[] = [
            'order_no' => $orderNo,
            'tracking' => $tn,
            'name' => trim((string) ($row['name'] ?? '')),
            'items' => $items,
            'courier' => trim((string) ($row['courier'] ?? 'SPX')),
        ];
    }
    if ($normalized === []) {
        throw new RuntimeException('No complete rows to prepare.');
    }

    $excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'Shopee SPX — Online Orders');

    $preview = [];
    foreach ($normalized as $row) {
        $preview[] = [
            'order_no' => $row['order_no'],
            'name' => $row['name'],
            'items' => $row['items'],
            'courier' => $row['courier'],
            'tracking_number' => $row['tracking'],
        ];
    }

    return [
        'excel_token' => $excelToken,
        'row_count' => count($preview),
        'rows' => $preview,
    ];
}

/**
 * @param list<array{name?:string,tracking_number:string,items:string,courier?:string,order_no?:?string}> $rows
 * @return array{excel_token: string, row_count: int, rows: list<array<string, mixed>>}
 */
function online_orders_tool_vip_prepare(array $rows): array
{
    $normalized = [];
    foreach ($rows as $row) {
        $tn = trim((string) ($row['tracking_number'] ?? ''));
        $items = online_orders_normalize_items_display(trim((string) ($row['items'] ?? '')));
        if ($tn === '' || $items === '') {
            continue;
        }
        $normalized[] = [
            'order_no' => isset($row['order_no']) && $row['order_no'] !== '' && $row['order_no'] !== null
                ? (string) $row['order_no'] : null,
            'tracking' => $tn,
            'name' => trim((string) ($row['name'] ?? '')),
            'items' => $items,
            'courier' => trim((string) ($row['courier'] ?? 'VIP')),
        ];
    }
    if ($normalized === []) {
        throw new RuntimeException('No complete VIP rows to prepare.');
    }

    $excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'VIP — Online Orders');
    $preview = [];
    foreach ($normalized as $row) {
        $preview[] = [
            'order_no' => $row['order_no'],
            'name' => $row['name'],
            'items' => $row['items'],
            'courier' => $row['courier'],
            'tracking_number' => $row['tracking'],
        ];
    }

    return [
        'excel_token' => $excelToken,
        'row_count' => count($preview),
        'rows' => $preview,
    ];
}

function online_orders_vip_arranger_stash_prune(): void
{
    ims_session_start();
    if (!isset($_SESSION['vip_arranger_stash']) || !is_array($_SESSION['vip_arranger_stash'])) {
        $_SESSION['vip_arranger_stash'] = [];

        return;
    }
    foreach ($_SESSION['vip_arranger_stash'] as $token => $meta) {
        if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
            unset($_SESSION['vip_arranger_stash'][$token]);
        }
    }
}

/**
 * @param list<array{parcel_ref:string,receiver:string,phone?:string,items:string,courier_hint?:?string}> $rows
 * @return array{arranger_token:string,row_count:int}
 */
function online_orders_vip_arranger_stash_save(array $rows): array
{
    online_orders_vip_arranger_stash_prune();
    $clean = [];
    foreach ($rows as $row) {
        $parcelRef = trim((string) ($row['parcel_ref'] ?? ''));
        $items = trim((string) ($row['items'] ?? ''));
        $receiver = trim((string) ($row['receiver'] ?? ''));
        if ($parcelRef === '' || $items === '' || $receiver === '') {
            continue;
        }
        $clean[] = [
            'parcel_ref' => $parcelRef,
            'receiver' => $receiver,
            'phone' => trim((string) ($row['phone'] ?? '')),
            'items' => online_orders_normalize_items_display($items),
            'courier_hint' => isset($row['courier_hint']) && $row['courier_hint'] !== ''
                ? (string) $row['courier_hint'] : null,
        ];
    }
    if ($clean === []) {
        throw new RuntimeException('No complete arranged VIP rows to save.');
    }

    $token = bin2hex(random_bytes(16));
    ims_session_start();
    if (!isset($_SESSION['vip_arranger_stash']) || !is_array($_SESSION['vip_arranger_stash'])) {
        $_SESSION['vip_arranger_stash'] = [];
    }
    $_SESSION['vip_arranger_stash'][$token] = [
        'rows' => $clean,
        'expires' => time() + 7200,
        'created_at' => date('c'),
    ];
    $_SESSION['vip_arranger_last_token'] = $token;

    return [
        'arranger_token' => $token,
        'row_count' => count($clean),
    ];
}

/**
 * @return array{rows:list<array<string,mixed>>,created_at:?string}
 */
function online_orders_vip_arranger_stash_get(string $token): array
{
    online_orders_vip_arranger_stash_prune();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    if ($token === '') {
        throw new RuntimeException('Invalid arranger token.');
    }
    $stash = $_SESSION['vip_arranger_stash'] ?? [];
    if (!is_array($stash) || !isset($stash[$token]) || !is_array($stash[$token])) {
        throw new RuntimeException('Arranged VIP rows expired or not found. Run Excel arranger again.');
    }
    $meta = $stash[$token];
    $rows = $meta['rows'] ?? [];
    if (!is_array($rows) || $rows === []) {
        throw new RuntimeException('Arranged VIP rows are empty.');
    }

    return [
        'rows' => $rows,
        'created_at' => isset($meta['created_at']) ? (string) $meta['created_at'] : null,
    ];
}

function online_orders_vip_arranger_latest_token(): ?string
{
    online_orders_vip_arranger_stash_prune();
    ims_session_start();
    $token = isset($_SESSION['vip_arranger_last_token']) ? (string) $_SESSION['vip_arranger_last_token'] : '';
    if ($token === '') {
        return null;
    }
    try {
        online_orders_vip_arranger_stash_get($token);

        return $token;
    } catch (RuntimeException) {
        return null;
    }
}

function online_orders_vip_courier_label(?string $profileId, ?string $courierHint = null): string
{
    if ($courierHint !== null && $courierHint !== '') {
        return $courierHint;
    }
    $id = strtolower(trim((string) $profileId));

    return match ($id) {
        'spx' => 'SPX',
        'jnt', 'jnt_tracking', 'vip_order_list' => 'J&T Express',
        default => 'VIP',
    };
}

/**
 * @return array{
 *   excel_token:string,
 *   row_count:int,
 *   matched:int,
 *   unmatched:list<array{parcel_ref:string,receiver:string,phone?:string}>,
 *   rows:list<array<string,mixed>>,
 *   tracking_profile_id:string,
 *   tracking_profile_label:string
 * }
 */
function online_orders_tool_vip_merge(string $arrangerToken, string $trackingExcelToken, ?string $courierOverride = null): array
{
    $arranger = online_orders_vip_arranger_stash_get($arrangerToken);
    $trackingPath = order_remarks_resolve_stash_path($trackingExcelToken);
    $override = $courierOverride !== null && $courierOverride !== '' ? $courierOverride : null;
    if ($override === 'jnt') {
        $override = 'jnt_tracking';
    }
    $tracking = order_remarks_parse_tracking_map($trackingPath, $override);

    $map = [];
    foreach ($tracking['refs'] as $refRow) {
        if (!is_array($refRow)) {
            continue;
        }
        $ref = vip_normalize_parcel_ref((string) ($refRow['ref'] ?? ''));
        $tn = trim((string) ($refRow['tracking'] ?? ''));
        if ($ref === '' || $tn === '') {
            continue;
        }
        $map[$ref] = $tn;
    }
    if ($map === []) {
        throw new RuntimeException('No tracking numbers found in the courier export.');
    }

    $profileId = (string) ($tracking['profileId'] ?? '');
    $courierLabel = online_orders_vip_courier_label($profileId, null);
    if ($profileId === 'spx') {
        $courierLabel = 'SPX';
    }

    $matchedRows = [];
    $unmatched = [];
    foreach ($arranger['rows'] as $row) {
        $parcelRef = vip_normalize_parcel_ref((string) ($row['parcel_ref'] ?? ''));
        $trackingNo = $map[$parcelRef] ?? null;
        if ($trackingNo === null || $trackingNo === '') {
            $unmatched[] = [
                'parcel_ref' => (string) ($row['parcel_ref'] ?? ''),
                'receiver' => (string) ($row['receiver'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
            ];
            continue;
        }
        $matchedRows[] = [
            'order_no' => null,
            'name' => (string) ($row['receiver'] ?? ''),
            'items' => (string) ($row['items'] ?? ''),
            'courier' => online_orders_vip_courier_label(
                $profileId,
                isset($row['courier_hint']) ? (string) $row['courier_hint'] : null
            ) ?: $courierLabel,
            'tracking_number' => $trackingNo,
        ];
    }

    if ($matchedRows === []) {
        throw new RuntimeException(
            'No arranged rows matched the tracking export. Check Parcel Name vs Order Number / Customer Reference.'
        );
    }

    $pdo = ims_pdo();
    online_orders_assert_no_duplicate_tracking($pdo, 'vip', $matchedRows);

    $prep = online_orders_tool_vip_prepare($matchedRows);

    return [
        'excel_token' => $prep['excel_token'],
        'row_count' => $prep['row_count'],
        'matched' => count($matchedRows),
        'unmatched' => $unmatched,
        'rows' => $prep['rows'],
        'tracking_profile_id' => $profileId,
        'tracking_profile_label' => (string) ($tracking['profileLabel'] ?? $profileId),
    ];
}

/**
 * Universal stamp: PDF + prepared Excel → stamp + parse + append lines.
 *
 * @param list<string> $pdfTokens
 * @param list<string>|string $excelTokens
 * @return array{stamp: array<string, mixed>, lines_added: int, run_id: int}
 */
function online_orders_run_unified_stamp(
    PDO $pdo,
    int $userId,
    int $batchId,
    array $pdfTokens,
    string|array $excelTokens,
    string $pipelineTag,
    ?string $partnerSeller = null
): array
{
    $pipelineTag = online_orders_normalize_pipeline($pipelineTag);
    if ($pipelineTag === 'unified') {
        throw new RuntimeException('Invalid pipeline.');
    }
    $partnerSeller = online_orders_require_partner_seller_for_pipeline($pipelineTag, $partnerSeller);

    online_orders_assert_active_batch($pdo, $userId, $batchId);

    $excelTokenList = is_array($excelTokens) ? array_values($excelTokens) : [trim((string) $excelTokens)];
    $excelTokenList = array_filter(array_map(static fn ($t) => trim((string) $t), $excelTokenList));
    if ($excelTokenList === []) {
        throw new RuntimeException('excel_token required.');
    }

    $pdfs = waybill_stamper_resolve_stash_tokens($pdfTokens, 'pdf');
    $excels = waybill_stamper_resolve_stash_tokens($excelTokenList, 'excel');
    $stashRelease = array_merge($pdfTokens, $excelTokenList);

    $parsed = online_orders_parse_ipyx_excel_paths($excels);
    $lines = online_orders_normalize_line_payloads(array_map(static function (array $row): array {
        return [
            'order_no' => $row['order_no'],
            'name' => $row['name'],
            'items' => $row['items'],
            'courier' => $row['courier'],
            'tracking_number' => $row['tracking_number'],
        ];
    }, $parsed));

    if ($lines === []) {
        throw new RuntimeException('No order rows found in prepared Excel.');
    }

    online_orders_assert_no_duplicate_tracking($pdo, $pipelineTag, $lines);

    $runId = online_orders_run_start($pdo, $batchId, $pipelineTag);
    try {
        $stamp = waybill_stamper_process_uploads($pdfs, $excels);
        $added = online_orders_lines_append($pdo, $batchId, $runId, $pipelineTag, $lines, $partnerSeller);
        $meta = [
            'stamp' => [
                'matched' => $stamp['matched'],
                'total' => $stamp['total'],
                'unmatched' => $stamp['unmatched'],
            ],
            'lines_added' => $added,
            'source' => 'unified_stamp',
        ];
        if ($partnerSeller !== null) {
            $meta['partner_seller'] = $partnerSeller;
        }
        online_orders_run_complete($pdo, $runId, $meta);
        waybill_stamper_release_stash_tokens($stashRelease);

        return ['stamp' => $stamp, 'lines_added' => $added, 'run_id' => $runId];
    } catch (Throwable $e) {
        waybill_stamper_release_stash_tokens($stashRelease);
        $fail = $pdo->prepare('UPDATE csr_online_batch_runs SET status = ? WHERE id = ?');
        $fail->execute(['failed', $runId]);
        throw $e;
    }
}

/**
 * @param list<string> $pdfTokens
 * @param list<string> $excelTokens
 * @return array{stamp: array<string, mixed>, lines_added: int, run_id: int}
 */
function online_orders_run_partner_stamp(
    PDO $pdo,
    int $userId,
    int $batchId,
    array $pdfTokens,
    array $excelTokens
): array
{
    return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelTokens, 'partner');
}

/**
 * @param list<string> $pdfTokens
 */
function online_orders_run_vip_stamp(
    PDO $pdo,
    int $userId,
    int $batchId,
    array $pdfTokens,
    string $excelToken
): array
{
    return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelToken, 'vip');
}

/**
 * Build iPYX excel from resolved Shopee rows and stash for waybill.
 *
 * @param list<array{order_no?:?string,tracking:string,name:string,items:string,courier:string}> $rows
 */
function online_orders_build_ipyx_excel_from_rows(array $rows, string $label): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    require_once $autoload;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Orders');

    $headerRow = 4;
    $firstData = 6;
    $headers = [
        2 => 'Order No.',
        3 => 'Name',
        4 => 'Item/s',
        5 => 'Note',
        7 => 'Courier',
        8 => 'Tracking Number',
    ];
    foreach ($headers as $col => $text) {
        $sheet->setCellValue(online_orders_coord($col, $headerRow), $text);
        $sheet->getStyle(online_orders_coord($col, $headerRow))->getFont()->setBold(true);
    }
    $sheet->setCellValue('B1', $label);

    $r = $firstData;
    foreach ($rows as $row) {
        $orderNo = $row['order_no'] ?? '';
        $sheet->setCellValue(online_orders_coord(2, $r), $orderNo !== null && $orderNo !== '' ? (string) $orderNo : '');
        $sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['name'] ?? ''));
        $sheet->getCell(online_orders_coord(4, $r))
            ->setValueExplicit((string) ($row['items'] ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue(online_orders_coord(5, $r), '');
        $sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));
        $sheet->getCell(online_orders_coord(8, $r))
            ->setValueExplicit((string) ($row['tracking'] ?? ''), DataType::TYPE_STRING);
        $r++;
    }

    $dir = online_orders_storage_root() . '/work';
    $path = $dir . '/ipyx_' . bin2hex(random_bytes(8)) . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($path);
    $spreadsheet->disconnectWorksheets();

    $token = bin2hex(random_bytes(16));
    ims_session_start();
    if (!isset($_SESSION['waybill_stash']) || !is_array($_SESSION['waybill_stash'])) {
        $_SESSION['waybill_stash'] = [];
    }
    $_SESSION['waybill_stash'][$token] = [
        'path' => $path,
        'kind' => 'excel',
        'expires' => time() + 3600,
        'filename' => 'Online_Orders_Prep_' . date('Y-m-d') . '.xlsx',
    ];

    return $token;
}

/**
 * @param list<array{page?:int,order_no?:?string,tracking:string,name?:string,items:string,courier?:string}> $rows
 */
function online_orders_run_shopee_resolve(PDO $pdo, int $userId, int $batchId, array $rows): array
{
    online_orders_assert_active_batch($pdo, $userId, $batchId);
    $prep = online_orders_tool_spx_prepare($rows);

    return [
        'excel_token' => $prep['excel_token'],
        'run_id' => 0,
        'row_count' => $prep['row_count'],
        'rows' => $prep['rows'],
    ];
}

/**
 * Stamp only (lines already appended during shopee resolve).
 *
 * @param list<string> $pdfTokens
 */
function online_orders_run_shopee_stamp(
    PDO $pdo,
    int $userId,
    int $batchId,
    array $pdfTokens,
    string $excelToken
): array
{
    return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelToken, 'shopee_spx');
}

function online_orders_merge_master_workbook(PDO $pdo, int $batchId): string
{
    $st = $pdo->prepare(
        'SELECT order_no, recipient_name, items, courier, tracking_number
         FROM csr_online_order_lines WHERE batch_id = ? ORDER BY id ASC'
    );
    $st->execute([$batchId]);
    $dbRows = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($dbRows === []) {
        throw new RuntimeException('No order lines in this batch. Process at least one pipeline first.');
    }

    return online_orders_build_print_workbook_meta($dbRows);
}

/**
 * @param list<array{order_no?:?string,recipient_name?:string,items?:string,courier?:string,tracking_number?:string}> $rows
 */
function online_orders_build_print_workbook_meta(array $rows): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    require_once $autoload;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Print Orders');

    $headerRow = 4;
    $firstData = 6;
    $sheet->setCellValue('B1', 'Online Orders — Master Print Sheet');
    $sheet->setCellValue('B2', 'Generated ' . date('Y-m-d H:i'));
    $headers = [
        2 => 'Order No.',
        3 => 'Name',
        4 => 'Item/s',
        5 => 'Note',
        7 => 'Courier',
        8 => 'Tracking Number',
    ];
    foreach ($headers as $col => $text) {
        $coord = online_orders_coord($col, $headerRow);
        $cell = $sheet->getCell($coord);
        $cell->setValue($text);
        $cell->getStyle()->getFont()->setBold(true);
        $cell->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('C8900A');
        $cell->getStyle()->getFont()->getColor()->setRGB('FFFFFF');
        $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    $r = $firstData;
    $seq = 1;
    foreach ($rows as $row) {
        $sheet->setCellValue(online_orders_coord(1, $r), $seq++);
        $sheet->setCellValue(online_orders_coord(2, $r), (string) ($row['order_no'] ?? ''));
        $sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['recipient_name'] ?? ''));
        $sheet->getCell(online_orders_coord(4, $r))
            ->setValueExplicit((string) ($row['items'] ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue(online_orders_coord(5, $r), '');
        $sheet->setCellValue(online_orders_coord(6, $r), '');
        $sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));
        $sheet->getCell(online_orders_coord(8, $r))
            ->setValueExplicit((string) ($row['tracking_number'] ?? ''), DataType::TYPE_STRING);
        $r++;
    }

    foreach ([1 => 6, 2 => 18, 3 => 28, 4 => 42, 5 => 8, 6 => 4, 7 => 22, 8 => 24] as $col => $width) {
        $sheet->getColumnDimensionByColumn($col)->setWidth($width);
    }

    $filename = 'Online_Orders_Print_' . date('Y-m-d') . '.xlsx';
    $path = online_orders_storage_root() . '/outputs/master_' . bin2hex(random_bytes(8)) . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($path);
    $spreadsheet->disconnectWorksheets();

    return json_encode(['path' => $path, 'filename' => $filename], JSON_UNESCAPED_SLASHES);
}

function online_orders_register_master_download(string $metaJson): string
{
    $meta = json_decode($metaJson, true);
    if (!is_array($meta) || empty($meta['path']) || empty($meta['filename'])) {
        throw new RuntimeException('Invalid download metadata.');
    }
    ims_session_start();
    if (!isset($_SESSION['online_orders_downloads']) || !is_array($_SESSION['online_orders_downloads'])) {
        $_SESSION['online_orders_downloads'] = [];
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['online_orders_downloads'][$token] = [
        'path' => (string) $meta['path'],
        'filename' => (string) $meta['filename'],
        'expires' => time() + 3600,
    ];
    return $token;
}

function online_orders_serve_download(string $token): void
{
    online_orders_require_access();
    ims_session_start();
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
    $meta = $_SESSION['online_orders_downloads'][$token] ?? null;
    if (!is_array($meta) || (int) ($meta['expires'] ?? 0) < time()) {
        http_response_code(404);
        echo 'Download expired or not found.';
        exit;
    }
    $path = (string) ($meta['path'] ?? '');
    $filename = (string) ($meta['filename'] ?? 'download.xlsx');
    if ($path === '' || !is_file($path)) {
        http_response_code(404);
        echo 'File not found.';
        exit;
    }
    unset($_SESSION['online_orders_downloads'][$token]);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}

/**
 * @return array{batch: array<string, mixed>, download_token: string, download_filename: string}
 */
function online_orders_batch_finalize(PDO $pdo, int $userId, int $batchId): array
{
    online_orders_assert_active_batch($pdo, $userId, $batchId);

    require_once __DIR__ . '/online_order_parcels.php';

    $metaJson = online_orders_merge_master_workbook($pdo, $batchId);
    $meta = json_decode($metaJson, true);
    $filename = is_array($meta) ? (string) ($meta['filename'] ?? 'Online_Orders.xlsx') : 'Online_Orders.xlsx';
    $token = online_orders_register_master_download($metaJson);

    $st = $pdo->prepare(
        'UPDATE csr_online_batches SET status = ?, completed_at = NOW(), master_file_token = ?, master_filename = ? WHERE id = ?'
    );
    $st->execute(['processed', $token, $filename, $batchId]);

    $promote = online_parcels_promote_from_batch($pdo, $batchId);

    $batch = online_orders_batch_get($pdo, $userId, $batchId);
    if ($batch === null) {
        throw new RuntimeException('Batch missing after finalize.');
    }

    return [
        'batch' => $batch,
        'download_token' => $token,
        'download_filename' => $filename,
        'parcels_promoted' => $promote['promoted'],
        'parcels_skipped' => $promote['skipped'],
        'parcels_errors' => $promote['errors'],
    ];
}
