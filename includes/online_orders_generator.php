<?php

declare(strict_types=1);

/**
 * CSR Online Orders Generator — batch orchestration, line storage, master iPYX merge.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/waybill_stamper.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = (string) $sheet->getTitle();
            $lower = strtolower($sheetName);
            $skip = false;
            foreach ($skipSheets as $s) {
                if (str_contains($lower, $s)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

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

            if ($headerIdx === null) {
                continue;
            }

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
                $key = preg_replace('/\s+/', '', strtoupper($tn));
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

/**
 * @param list<array{order_no?:?string,name?:string,items:string,courier?:string,tracking_number:string}> $lines
 */
function online_orders_lines_append(
    PDO $pdo,
    int $batchId,
    int $runId,
    string $pipeline,
    array $lines
): int
{
    $ins = $pdo->prepare(
        'INSERT INTO csr_online_order_lines
            (batch_id, run_id, order_no, recipient_name, items, courier, tracking_number, source_pipeline)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $count = 0;
    foreach ($lines as $line) {
        $tn = trim((string) ($line['tracking_number'] ?? ''));
        $items = trim((string) ($line['items'] ?? ''));
        if ($tn === '' || $items === '') {
            continue;
        }
        $ins->execute([
            $batchId,
            $runId > 0 ? $runId : null,
            isset($line['order_no']) && $line['order_no'] !== '' && $line['order_no'] !== null
                ? (string) $line['order_no'] : null,
            trim((string) ($line['name'] ?? '')),
            $items,
            trim((string) ($line['courier'] ?? '')),
            $tn,
            $pipeline,
        ]);
        $count++;
    }
    return $count;
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
    online_orders_assert_active_batch($pdo, $userId, $batchId);

    $pdfs = waybill_stamper_resolve_stash_tokens($pdfTokens, 'pdf');
    $excels = waybill_stamper_resolve_stash_tokens($excelTokens, 'excel');
    $stashRelease = array_merge($pdfTokens, $excelTokens);

    $runId = online_orders_run_start($pdo, $batchId, 'partner');
    try {
        $stamp = waybill_stamper_process_uploads($pdfs, $excels);
        $parsed = online_orders_parse_ipyx_excel_paths($excels);
        $lines = [];
        foreach ($parsed as $row) {
            $lines[] = [
                'order_no' => $row['order_no'],
                'name' => $row['name'],
                'items' => $row['items'],
                'courier' => $row['courier'],
                'tracking_number' => $row['tracking_number'],
            ];
        }
        $added = online_orders_lines_append($pdo, $batchId, $runId, 'partner', $lines);
        online_orders_run_complete($pdo, $runId, [
            'stamp' => [
                'matched' => $stamp['matched'],
                'total' => $stamp['total'],
                'unmatched' => $stamp['unmatched'],
            ],
            'lines_added' => $added,
        ]);
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
 */
function online_orders_run_vip_stamp(
    PDO $pdo,
    int $userId,
    int $batchId,
    array $pdfTokens,
    string $excelToken
): array
{
    online_orders_assert_active_batch($pdo, $userId, $batchId);

    $pdfs = waybill_stamper_resolve_stash_tokens($pdfTokens, 'pdf');
    $excels = waybill_stamper_resolve_stash_tokens([$excelToken], 'excel');
    $stashRelease = array_merge($pdfTokens, [$excelToken]);

    $runId = online_orders_run_start($pdo, $batchId, 'vip');
    try {
        $stamp = waybill_stamper_process_uploads($pdfs, $excels);
        $parsed = online_orders_parse_ipyx_excel_paths($excels);
        $lines = [];
        foreach ($parsed as $row) {
            $lines[] = [
                'order_no' => $row['order_no'],
                'name' => $row['name'],
                'items' => $row['items'],
                'courier' => $row['courier'] !== '' ? $row['courier'] : 'VIP',
                'tracking_number' => $row['tracking_number'],
            ];
        }
        $added = online_orders_lines_append($pdo, $batchId, $runId, 'vip', $lines);
        online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'stamp_matched' => $stamp['matched']]);
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
 * Build iPYX excel from resolved Shopee rows and stash for waybill.
 *
 * @param list<array{tracking:string,name:string,items:string,courier:string}> $rows
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
        $sheet->setCellValue(online_orders_coord(2, $r), '');
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
    ];

    return $token;
}

/**
 * @param list<array{page?:int,tracking:string,name?:string,items:string,courier?:string}> $rows
 */
function online_orders_run_shopee_resolve(PDO $pdo, int $userId, int $batchId, array $rows): array
{
    online_orders_assert_active_batch($pdo, $userId, $batchId);
    $normalized = [];
    $linePayload = [];
    foreach ($rows as $row) {
        $tn = trim((string) ($row['tracking'] ?? ''));
        $items = trim((string) ($row['items'] ?? ''));
        if ($tn === '' || $items === '') {
            continue;
        }
        $normalized[] = [
            'tracking' => $tn,
            'name' => trim((string) ($row['name'] ?? '')),
            'items' => $items,
            'courier' => trim((string) ($row['courier'] ?? 'SPX')),
        ];
        $linePayload[] = [
            'order_no' => null,
            'name' => trim((string) ($row['name'] ?? '')),
            'items' => $items,
            'courier' => trim((string) ($row['courier'] ?? 'SPX')),
            'tracking_number' => $tn,
        ];
    }
    if ($normalized === []) {
        throw new RuntimeException('No complete rows to save.');
    }

    $runId = online_orders_run_start($pdo, $batchId, 'shopee_spx');
    $excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'Shopee SPX — Online Orders');
    online_orders_lines_append($pdo, $batchId, $runId, 'shopee_spx', $linePayload);
    online_orders_run_complete($pdo, $runId, ['excel_token' => $excelToken, 'row_count' => count($normalized)]);

    return ['excel_token' => $excelToken, 'run_id' => $runId, 'row_count' => count($normalized)];
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
    online_orders_assert_active_batch($pdo, $userId, $batchId);

    $pdfs = waybill_stamper_resolve_stash_tokens($pdfTokens, 'pdf');
    $excels = waybill_stamper_resolve_stash_tokens([$excelToken], 'excel');
    $stashRelease = array_merge($pdfTokens, [$excelToken]);

    try {
        $stamp = waybill_stamper_process_uploads($pdfs, $excels);
        waybill_stamper_release_stash_tokens($stashRelease);
        return ['stamp' => $stamp, 'lines_added' => 0];
    } catch (Throwable $e) {
        waybill_stamper_release_stash_tokens($stashRelease);
        throw $e;
    }
}

function online_orders_merge_master_workbook(PDO $pdo, int $batchId): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    require_once $autoload;

    $st = $pdo->prepare(
        'SELECT order_no, recipient_name, items, courier, tracking_number
         FROM csr_online_order_lines WHERE batch_id = ? ORDER BY id ASC'
    );
    $st->execute([$batchId]);
    $dbRows = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($dbRows === []) {
        throw new RuntimeException('No order lines in this batch. Process at least one pipeline first.');
    }

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
    foreach ($dbRows as $row) {
        $sheet->setCellValue(online_orders_coord(1, $r), $seq++);
        $sheet->setCellValue(online_orders_coord(2, $r), (string) ($row['order_no'] ?? ''));
        $sheet->setCellValue(online_orders_coord(3, $r), (string) $row['recipient_name']);
        $sheet->getCell(online_orders_coord(4, $r))
            ->setValueExplicit((string) $row['items'], DataType::TYPE_STRING);
        $sheet->setCellValue(online_orders_coord(5, $r), '');
        $sheet->setCellValue(online_orders_coord(6, $r), '');
        $sheet->setCellValue(online_orders_coord(7, $r), (string) $row['courier']);
        $sheet->getCell(online_orders_coord(8, $r))
            ->setValueExplicit((string) $row['tracking_number'], DataType::TYPE_STRING);
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
