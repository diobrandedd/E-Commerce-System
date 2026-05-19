<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/** Column D is index 3 (0-based). */
const SP_SORT_COL_D = 3;

const SP_SORT_COURIER_COLORS = [
    'flash' => 'FFF9C4',
    'spx' => 'FFE0B2',
];

function sp_sort_storage_dir(): string
{
    require_once __DIR__ . '/rts_storage.php';

    return rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT);
}

function sp_sort_upload_dir(): string
{
    require_once __DIR__ . '/rts_storage.php';

    $dir = rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT) . '/tmp';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function sp_sort_numeric_d(mixed $value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }
    if (is_numeric($value)) {
        return (float) $value;
    }
    $s = preg_replace('/[^\d.\-]/', '', (string) $value);
    return $s === '' || $s === '-' ? 0.0 : (float) $s;
}

/**
 * Sort key from Item/s column (D): leading quantity, then item text.
 *
 * @param list<mixed> $row
 * @return array{0: float, 1: string}
 */
function sp_sort_item_sort_key(array $row): array
{
    $norm = sp_sort_normalize_pyx_row($row);
    $item = trim((string) ($norm[3] ?? ''));

    return [sp_sort_numeric_d($item), strtolower($item)];
}

/**
 * Sort rows by Item/s (column D); entire rows move together (stable tie on original index).
 *
 * @param list<list<mixed>> $rows
 * @return list<list<mixed>>
 */
function sp_sort_rows_by_column_d(array $rows, string $dir = 'asc'): array
{
    $indexed = [];
    foreach ($rows as $i => $row) {
        [$qty, $itemText] = sp_sort_item_sort_key($row);
        $indexed[] = ['i' => $i, 'row' => $row, 'qty' => $qty, 'item' => $itemText];
    }
    usort($indexed, static function (array $a, array $b) use ($dir): int {
        $cmp = $a['qty'] <=> $b['qty'];
        if ($cmp === 0) {
            $cmp = strcmp($a['item'], $b['item']);
        }
        if ($cmp === 0) {
            return $a['i'] <=> $b['i'];
        }

        return $dir === 'desc' ? -$cmp : $cmp;
    });

    return array_map(static fn (array $x) => $x['row'], $indexed);
}

/**
 * @return array{courier:string,shop:string,channel_text:?string,channel_col:?int}
 */
function sp_sort_analyze_row(array $row): array
{
    $channelText = null;
    $channelCol = null;
    $courier = 'unknown';
    $shop = 'unknown';

    foreach ($row as $col => $cell) {
        $text = trim((string) $cell);
        if ($text === '') {
            continue;
        }
        $lower = strtolower($text);
        $isChannel = str_contains($lower, 'express')
            || str_contains($lower, 'lazada-ph')
            || str_contains($lower, 'shopee-ph')
            || str_contains($lower, 'tiktok-ph');
        if (!$isChannel) {
            continue;
        }
        $channelText = $text;
        $channelCol = (int) $col;
        if (str_contains($lower, 'shopee')) {
            $shop = 'shopee';
        } elseif (str_contains($lower, 'lazada')) {
            $shop = 'lazada';
        } elseif (str_contains($lower, 'tiktok')) {
            $shop = 'tiktok';
        }
        if (str_contains($lower, 'flash')) {
            $courier = 'flash';
        } elseif (str_contains($lower, 'spx')) {
            $courier = 'spx';
        } elseif (str_contains($lower, 'j&t') || str_contains($lower, 'jnt')) {
            $courier = 'jnt';
        }
        break;
    }

    if ($channelText === null) {
        $joined = strtolower(implode(' ', array_map('strval', $row)));
        if (str_contains($joined, 'flash')) {
            $courier = 'flash';
        } elseif (str_contains($joined, 'spx')) {
            $courier = 'spx';
        } elseif (str_contains($joined, 'j&t') || str_contains($joined, 'jnt')) {
            $courier = 'jnt';
        }
        if (str_contains($joined, 'shopee')) {
            $shop = 'shopee';
        } elseif (str_contains($joined, 'lazada')) {
            $shop = 'lazada';
        } elseif (str_contains($joined, 'tiktok')) {
            $shop = 'tiktok';
        }
    }

    return [
        'courier' => $courier,
        'shop' => $shop,
        'channel_text' => $channelText,
        'channel_col' => $channelCol,
    ];
}

function sp_sort_excel_cell_plain(mixed $val): string
{
    if ($val === null || $val === '') {
        return '';
    }
    if (is_float($val)) {
        if (abs($val) >= 1e11 || (floor($val) == $val && abs($val) < 1e16)) {
            return sprintf('%.0f', $val);
        }
    }
    if (is_int($val)) {
        return (string) $val;
    }

    return trim((string) $val);
}

/**
 * @return list<list<mixed>>
 */
function sp_sort_read_sheet_rows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
{
    $rows = $sheet->toArray(null, true, true, false);
    $hr = (int) $sheet->getHighestRow();
    for ($r = 1; $r <= $hr; $r++) {
        $idx = $r - 1;
        if (!isset($rows[$idx])) {
            continue;
        }
        $orderCell = $sheet->getCell('B' . $r);
        $formatted = $orderCell->getFormattedValue();
        if ($formatted !== null && trim((string) $formatted) !== '') {
            $rows[$idx][1] = sp_sort_excel_cell_plain($formatted);
        } elseif (isset($rows[$idx][1])) {
            $rows[$idx][1] = sp_sort_excel_cell_plain($rows[$idx][1]);
        }
    }

    return $rows;
}

function sp_sort_row_is_empty(array $row): bool
{
    foreach ($row as $cell) {
        if ($cell !== null && trim((string) $cell) !== '') {
            return false;
        }
    }

    return true;
}

function sp_sort_is_pyx_header_row(array $row): bool
{
    $b = strtolower(trim((string) ($row[1] ?? '')));

    return str_contains($b, 'order no');
}

/** First row of per-shop export sheets (# | Order No. | Name | Item/s | Courier). */
function sp_sort_is_simple_header_row(array $row): bool
{
    $a = strtolower(trim((string) ($row[0] ?? '')));
    if ($a === '#' || $a === 'no' || $a === 'no.') {
        return true;
    }

    return sp_sort_is_pyx_header_row($row);
}

function sp_sort_is_skippable_data_row(array $row): bool
{
    if (sp_sort_row_is_empty($row) || sp_sort_is_prepared_by_row($row)) {
        return true;
    }
    $norm = sp_sort_normalize_pyx_row($row);
    $order = strtolower(trim((string) ($norm[1] ?? '')));
    $name = strtolower(trim((string) ($norm[2] ?? '')));
    $item = strtolower(trim((string) ($norm[3] ?? '')));
    if ($order === 'order no.' || $order === 'order no' || $order === 'order number') {
        return true;
    }
    if ($item === 'item/s' || $item === 'items' || $item === 'item') {
        return true;
    }
    if ($name === 'name' && ($order === '' || $order === 'order no.')) {
        return true;
    }

    return $order === '' && $name === '' && $item === '';
}

/** Write order number without scientific notation; blank stays blank. */
function sp_sort_set_order_cell(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $coord, mixed $orderVal): void
{
    if ($orderVal === null || trim((string) $orderVal) === '') {
        $sheet->setCellValue($coord, '');

        return;
    }
    $s = trim((string) $orderVal);
    if (is_float($orderVal) || is_int($orderVal)) {
        $s = sprintf('%.0f', (float) $orderVal);
    }
    if (preg_match('/^[0-9.eE+\-]+$/', $s) && str_contains(strtolower($s), 'e')) {
        $s = sprintf('%.0f', (float) $s);
    }
    $sheet->setCellValueExplicit($coord, $s, DataType::TYPE_STRING);
    $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
}

function sp_sort_is_prepared_by_row(array $row): bool
{
    $b = strtolower(trim((string) ($row[1] ?? '')));

    return str_starts_with($b, 'prepared by');
}

/** @return array{brand:string,order_date_line:string,pickup_date_line:string,note_line_1:string,note_line_2:string} */
function sp_sort_default_print_meta(): array
{
    return [
        'brand' => 'iPYX',
        'order_date_line' => 'Order Date: ' . date('M j'),
        'pickup_date_line' => 'Pickup Date: ' . date('M j'),
        'note_line_1' => 'Please double check the multiple items and quantities.',
        'note_line_2' => 'WIth Asterisks(*), pwede maskin unsa nga variant.',
    ];
}

/**
 * @param array<string, mixed> $overrides
 * @return array{brand:string,order_date_line:string,pickup_date_line:string,note_line_1:string,note_line_2:string}
 */
function sp_sort_resolve_print_meta(array $overrides = []): array
{
    $meta = sp_sort_default_print_meta();
    foreach (['brand', 'order_date_line', 'pickup_date_line', 'note_line_1', 'note_line_2'] as $key) {
        if (isset($overrides[$key]) && trim((string) $overrides[$key]) !== '') {
            $meta[$key] = trim((string) $overrides[$key]);
        }
    }

    return $meta;
}

/**
 * Normalize to 5 columns: A=# (filled on export), B=order, C=name, D=item/s, E=courier.
 *
 * @param list<mixed> $row
 * @return list<mixed>
 */
function sp_sort_normalize_pyx_row(array $row): array
{
    $row = array_values($row);
    while (count($row) < 5) {
        $row[] = '';
    }
    // A=#, B=Order No., C=Name, D=Item/s, E=Courier (B may be blank)
    if (count($row) >= 5) {
        return [
            null,
            sp_sort_excel_cell_plain($row[1] ?? ''),
            sp_sort_excel_cell_plain($row[2] ?? ''),
            sp_sort_excel_cell_plain($row[3] ?? ''),
            sp_sort_excel_cell_plain($row[4] ?? ''),
        ];
    }

    return [
        null,
        sp_sort_excel_cell_plain($row[0] ?? ''),
        sp_sort_excel_cell_plain($row[1] ?? ''),
        sp_sort_excel_cell_plain($row[2] ?? ''),
        sp_sort_excel_cell_plain($row[3] ?? ''),
    ];
}

/**
 * @param list<list<mixed>> $rows
 * @return array{format:string,meta:array,header:?array,rows:list<array>,error:?string}
 */
function sp_sort_parse_sheet_rows(array $rows, bool $hasHeader): array
{
    if ($rows === []) {
        return ['format' => 'generic', 'meta' => sp_sort_default_print_meta(), 'header' => null, 'rows' => [], 'error' => 'Empty sheet.'];
    }

    if (count($rows) >= 2 && sp_sort_is_simple_header_row($rows[0]) && !str_contains(strtolower(trim((string) ($rows[0][1] ?? ''))), 'ipyx')) {
        $dataRows = [];
        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            if (sp_sort_is_skippable_data_row($row)) {
                continue;
            }
            $dataRows[] = sp_sort_normalize_pyx_row($row);
        }

        return [
            'format' => 'simple',
            'meta' => sp_sort_default_print_meta(),
            'header' => null,
            'rows' => $dataRows,
            'error' => $dataRows === [] ? 'No data rows found in shop export sheet.' : null,
        ];
    }

    if (count($rows) >= 4 && sp_sort_is_pyx_header_row($rows[3])) {
        $meta = sp_sort_default_print_meta();
        if (trim((string) ($rows[0][1] ?? '')) !== '') {
            $meta['brand'] = trim((string) $rows[0][1]);
        }
        if (trim((string) ($rows[1][1] ?? '')) !== '') {
            $meta['order_date_line'] = trim((string) $rows[1][1]);
        }
        if (trim((string) ($rows[2][1] ?? '')) !== '') {
            $meta['pickup_date_line'] = trim((string) $rows[2][1]);
        }
        if (trim((string) ($rows[0][3] ?? '')) !== '') {
            $meta['note_line_1'] = trim((string) $rows[0][3]);
        }
        if (trim((string) ($rows[1][3] ?? '')) !== '') {
            $meta['note_line_2'] = trim((string) $rows[1][3]);
        }

        $dataRows = [];
        for ($i = 4, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            if (sp_sort_is_skippable_data_row($row)) {
                continue;
            }
            $dataRows[] = sp_sort_normalize_pyx_row($row);
        }

        return [
            'format' => 'pyx',
            'meta' => $meta,
            'header' => null,
            'rows' => $dataRows,
            'error' => $dataRows === [] ? 'No data rows found in Pyxfood layout sheet.' : null,
        ];
    }

    $headerRow = null;
    $start = 0;
    if ($hasHeader) {
        $headerRow = $rows[0];
        $start = 1;
    }
    $dataRows = [];
    for ($i = $start, $n = count($rows); $i < $n; $i++) {
        $row = $rows[$i];
        if (sp_sort_is_skippable_data_row($row)) {
            continue;
        }
        $dataRows[] = sp_sort_normalize_pyx_row($row);
    }

    return [
        'format' => 'generic',
        'meta' => sp_sort_default_print_meta(),
        'header' => $headerRow,
        'rows' => $dataRows,
        'error' => $dataRows === [] ? 'No data rows found in the uploaded files.' : null,
    ];
}

/**
 * @param list<string> $filePaths absolute paths to xlsx/xls
 * @return array{format:string,meta:array,header:?array,rows:list<array>,error:?string}
 */
function sp_sort_merge_uploads(array $filePaths, bool $hasHeader): array
{
    $allRows = [];
    $format = 'generic';
    $meta = sp_sort_default_print_meta();
    $headerRow = null;

    foreach ($filePaths as $filePath) {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (Throwable $e) {
            return ['format' => 'generic', 'meta' => $meta, 'header' => null, 'rows' => [], 'error' => 'Could not read Excel: ' . $e->getMessage()];
        }
        $sheet = $spreadsheet->getActiveSheet();
        $rows = sp_sort_read_sheet_rows($sheet);
        if ($rows === []) {
            continue;
        }
        $parsed = sp_sort_parse_sheet_rows($rows, $hasHeader);
        if ($parsed['error'] !== null && $parsed['rows'] === []) {
            continue;
        }
        if ($format === 'generic' && $parsed['format'] === 'pyx') {
            $format = 'pyx';
            $meta = $parsed['meta'];
        }
        if ($headerRow === null && $parsed['header'] !== null) {
            $headerRow = $parsed['header'];
        }
        foreach ($parsed['rows'] as $row) {
            $allRows[] = $row;
        }
    }

    if ($allRows === []) {
        return ['format' => $format, 'meta' => $meta, 'header' => $headerRow, 'rows' => [], 'error' => 'No data rows found in the uploaded files.'];
    }

    return ['format' => $format, 'meta' => $meta, 'header' => $headerRow, 'rows' => $allRows, 'error' => null];
}

/**
 * Pyxfood print/export layout (matches SHOPEE TIKTOK sample sheets).
 *
 * @param list<list<mixed>> $dataRows normalized 5-column rows
 * @param list<array{courier:string,shop:string,channel_text:?string,channel_col:?int}> $metaPerRow
 * @param array<string, mixed> $printMeta
 */
function sp_sort_write_print_excel(
    array $dataRows,
    array $metaPerRow,
    string $outPath,
    array $printMeta,
    string $preparedBy = ''
): void {
    $meta = sp_sort_resolve_print_meta($printMeta);
    $preparedBy = trim($preparedBy);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Sheet1');

    $sheet->setCellValue('B1', $meta['brand']);
    $sheet->setCellValue('D1', $meta['note_line_1']);
    $sheet->setCellValue('B2', $meta['order_date_line']);
    $sheet->setCellValue('D2', $meta['note_line_2']);
    $sheet->setCellValue('B3', $meta['pickup_date_line']);

    $sheet->setCellValue('B4', 'Order No.');
    $sheet->setCellValue('C4', 'Name');
    $sheet->setCellValue('D4', 'Item/s');
    $sheet->setCellValue('E4', 'Courier');

    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('B2:B3')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('D1:D2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF9900']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
    ]);
    $sheet->getStyle('B4:E4')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);

    $dataStart = 5;
    foreach ($dataRows as $i => $rowData) {
        $rn = $dataStart + $i;
        $norm = sp_sort_normalize_pyx_row($rowData);
        $rowMeta = $metaPerRow[$i] ?? sp_sort_analyze_row($norm);

        $sheet->setCellValue('A' . $rn, $i + 1);
        sp_sort_set_order_cell($sheet, 'B' . $rn, $norm[1]);
        $sheet->setCellValue('C' . $rn, $norm[2]);
        $sheet->setCellValue('D' . $rn, $norm[3]);
        $sheet->setCellValue('E' . $rn, $norm[4]);

        $sheet->getStyle("A{$rn}:E{$rn}")->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        $sheet->getStyle("B{$rn}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D{$rn}")->getFont()->setBold(true);

        $courier = $rowMeta['courier'] ?? 'unknown';
        if (isset(SP_SORT_COURIER_COLORS[$courier])) {
            $sheet->getStyle('E' . $rn)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(SP_SORT_COURIER_COLORS[$courier]);
        }
    }

    $footerRow = $dataStart + count($dataRows) + 2;
    $sheet->setCellValue('B' . $footerRow, 'Prepared By: ');
    $sheet->setCellValue('C' . $footerRow, $preparedBy);
    $sheet->setCellValue('D' . $footerRow, date('F j, Y'));
    $sheet->getStyle('B' . $footerRow)->getFont()->setBold(true);

    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(28);
    $sheet->getColumnDimension('C')->setWidth(29);
    $sheet->getColumnDimension('D')->setWidth(59);
    $sheet->getColumnDimension('E')->setWidth(28);

    $sheet->freezePane('A5');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outPath);
}

/**
 * @param array{format:string,meta:array,header:?array,rows:list<array>} $merged
 * @param list<string> $sourceNames
 */
function sp_sort_create_batch(
    PDO $pdo,
    int $userId,
    array $merged,
    array $sourceNames,
    string $sortDir = 'asc'
): int {
    $dataRows = $merged['rows'];
    $printMeta = $merged['meta'] ?? sp_sort_default_print_meta();
    $sorted = sp_sort_rows_by_column_d($dataRows, $sortDir);
    $metaList = [];
    $sortKeys = [];
    foreach ($sorted as $row) {
        $norm = sp_sort_normalize_pyx_row($row);
        $meta = sp_sort_analyze_row($norm);
        $metaList[] = $meta;
        $sortKeys[] = trim((string) ($norm[3] ?? ''));
    }

    $filename = 'batch_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.xlsx';
    $outPath = sp_sort_storage_dir() . '/' . $filename;
    sp_sort_write_print_excel($sorted, $metaList, $outPath, $printMeta, '');

    $printMetaJson = json_encode($printMeta, JSON_UNESCAPED_UNICODE);
    $pdo->beginTransaction();
    try {
        try {
            $st = $pdo->prepare(
                'INSERT INTO sp_sort_batches (submitted_by_user_id, row_count, source_files, print_meta, merged_filename, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $userId,
                count($sorted),
                json_encode(array_values($sourceNames), JSON_UNESCAPED_UNICODE),
                $printMetaJson,
                $filename,
                'in_progress',
            ]);
        } catch (Throwable $e) {
            $st = $pdo->prepare(
                'INSERT INTO sp_sort_batches (submitted_by_user_id, row_count, source_files, merged_filename, status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $st->execute([
                $userId,
                count($sorted),
                json_encode(array_values($sourceNames), JSON_UNESCAPED_UNICODE),
                $filename,
                'in_progress',
            ]);
        }
        $batchId = (int) $pdo->lastInsertId();

        $ins = $pdo->prepare(
            'INSERT INTO sp_sort_rows (batch_id, row_num, sort_key_d, courier, shop, channel_text, row_data)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($sorted as $idx => $row) {
            $meta = $metaList[$idx];
            $ins->execute([
                $batchId,
                $idx + 1,
                $sortKeys[$idx],
                $meta['courier'],
                $meta['shop'],
                $meta['channel_text'],
                json_encode(array_values(sp_sort_normalize_pyx_row($row)), JSON_UNESCAPED_UNICODE),
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        if (is_file($outPath)) {
            @unlink($outPath);
        }
        throw $e;
    }

    return $batchId;
}

/**
 * @return list<array{row: list<mixed>, meta: array}>
 */
function sp_sort_load_batch_rows(PDO $pdo, int $batchId): array
{
    $st = $pdo->prepare(
        'SELECT row_data, courier, shop, channel_text FROM sp_sort_rows
         WHERE batch_id = ? ORDER BY row_num ASC'
    );
    $st->execute([$batchId]);
    $out = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $cells = json_decode((string) $row['row_data'], true);
        if (!is_array($cells)) {
            $cells = [];
        }
        $norm = sp_sort_normalize_pyx_row($cells);
        $out[] = [
            'row' => $norm,
            'meta' => [
                'courier' => (string) ($row['courier'] ?? 'unknown'),
                'shop' => (string) ($row['shop'] ?? 'unknown'),
                'channel_text' => $row['channel_text'] !== null ? (string) $row['channel_text'] : null,
                'channel_col' => 4,
            ],
        ];
    }

    return $out;
}

function sp_sort_regenerate_batch_file(
    PDO $pdo,
    int $batchId,
    string $preparedBy,
    array $printMetaOverrides = []
): void {
    $st = $pdo->prepare('SELECT merged_filename, print_meta FROM sp_sort_batches WHERE id = ? LIMIT 1');
    $st->execute([$batchId]);
    $batch = $st->fetch(PDO::FETCH_ASSOC);
    if (!$batch) {
        throw new RuntimeException('Batch not found.');
    }

    $storedMeta = json_decode((string) ($batch['print_meta'] ?? '{}'), true);
    if (!is_array($storedMeta)) {
        $storedMeta = [];
    }
    $printMeta = sp_sort_resolve_print_meta(array_merge($storedMeta, $printMetaOverrides));
    $preparedBy = trim($preparedBy);

    $loaded = sp_sort_load_batch_rows($pdo, $batchId);
    $dataRows = array_column($loaded, 'row');
    $metaList = array_column($loaded, 'meta');

    $filename = basename((string) ($batch['merged_filename'] ?? ''));
    if ($filename === '' || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
        $filename = 'batch_' . $batchId . '.xlsx';
    }
    $outPath = sp_sort_storage_dir() . '/' . $filename;
    sp_sort_write_print_excel($dataRows, $metaList, $outPath, $printMeta, $preparedBy);

    $upd = $pdo->prepare(
        'UPDATE sp_sort_batches SET print_meta = ?, prepared_by = ?, merged_filename = ? WHERE id = ?'
    );
    $upd->execute([
        json_encode($printMeta, JSON_UNESCAPED_UNICODE),
        $preparedBy !== '' ? $preparedBy : null,
        $filename,
        $batchId,
    ]);
}

/** @return array<string,int> */
function sp_sort_shop_counts(PDO $pdo, ?string $fromDate = null): array
{
    $sql = "SELECT shop, COUNT(*) AS cnt FROM sp_sort_rows r
            INNER JOIN sp_sort_batches b ON b.id = r.batch_id
            WHERE b.status = 'completed'";
    $params = [];
    if ($fromDate !== null) {
        $sql .= ' AND b.completed_at >= ?';
        $params[] = $fromDate;
    }
    $sql .= ' GROUP BY shop';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $out = ['shopee' => 0, 'lazada' => 0, 'tiktok' => 0, 'unknown' => 0];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $s = (string) ($row['shop'] ?? 'unknown');
        if (!isset($out[$s])) {
            $out[$s] = 0;
        }
        $out[$s] = (int) $row['cnt'];
    }
    return $out;
}

function sp_sort_tables_exist(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM sp_sort_batches LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** @return array<string,int> */
function sp_sort_courier_counts(PDO $pdo, ?string $fromDate = null): array
{
    $sql = "SELECT courier, COUNT(*) AS cnt FROM sp_sort_rows r
            INNER JOIN sp_sort_batches b ON b.id = r.batch_id
            WHERE b.status = 'completed'";
    $params = [];
    if ($fromDate !== null) {
        $sql .= ' AND b.completed_at >= ?';
        $params[] = $fromDate;
    }
    $sql .= ' GROUP BY courier';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $out = ['spx' => 0, 'jnt' => 0, 'flash' => 0, 'unknown' => 0];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $c = (string) ($row['courier'] ?? 'unknown');
        if (!isset($out[$c])) {
            $out[$c] = 0;
        }
        $out[$c] = (int) $row['cnt'];
    }
    return $out;
}
