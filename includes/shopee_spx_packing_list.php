<?php

declare(strict_types=1);

require_once __DIR__ . '/shopee_spx_product_info.php';
require_once __DIR__ . '/online_orders_generator.php';
require_once __DIR__ . '/waybill_stamper.php';

/**
 * @param list<string> $excelPaths
 * @return list<array<string, mixed>>
 */
function shopee_spx_packing_list_parse_paths(array $excelPaths): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer autoload missing (PhpSpreadsheet).');
    }
    require_once $autoload;

    $rows = [];
    $page = 1;

    foreach ($excelPaths as $path) {
        if (!is_file($path)) {
            continue;
        }
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        } catch (Throwable $e) {
            throw new RuntimeException('Cannot open packing list: ' . basename($path));
        }

        $sheet = $spreadsheet->getSheet(0);
        $highestRow = (int) $sheet->getHighestRow();
        $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString((string) $sheet->getHighestColumn());

        $headerIdx = null;
        $colMap = [];
        for ($r = 1; $r <= min($highestRow, 10); $r++) {
            $cells = [];
            for ($c = 1; $c <= $colIndex; $c++) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $cells[] = strtolower(trim((string) ($sheet->getCell($coord)->getValue() ?? '')));
            }
            $found = [];
            foreach ($cells as $idx => $cell) {
                if (in_array($cell, ['tracking_number', 'tracking number', 'tracking', 'waybill'], true)) {
                    $found['tracking'] = $idx;
                }
                if (in_array($cell, ['product_info', 'product info', 'items', 'item/s'], true)) {
                    $found['product_info'] = $idx;
                }
                if (in_array($cell, ['order_sn', 'order no', 'order_no'], true)) {
                    $found['order_sn'] = $idx;
                }
            }
            if (isset($found['tracking']) && isset($found['product_info'])) {
                $headerIdx = $r;
                $colMap = $found;
                break;
            }
        }

        if ($headerIdx === null) {
            $spreadsheet->disconnectWorksheets();
            throw new RuntimeException('Not a Shopee packing list — need tracking_number + product_info columns.');
        }

        $gcell = static function (string $key, int $rowNum) use ($sheet, $colMap, $colIndex): string {
            $idx = $colMap[$key] ?? null;
            if ($idx === null) {
                return '';
            }
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1) . $rowNum;
            $v = $sheet->getCell($coord)->getValue();
            if ($v === null) {
                return '';
            }
            if (is_float($v) && $v == (int) $v) {
                return (string) (int) $v;
            }

            return trim((string) $v);
        };

        for ($r = $headerIdx + 1; $r <= $highestRow; $r++) {
            $tracking = $gcell('tracking', $r);
            $productInfo = $gcell('product_info', $r);
            if ($tracking === '' && $productInfo === '') {
                continue;
            }
            if ($tracking === '') {
                continue;
            }

            $built = shopee_spx_build_items_from_product_info($productInfo);
            $missing = $built['missing_fields'];
            if ($built['items'] === '') {
                $missing = ['items'];
            }

            $rows[] = [
                'page' => $page++,
                'tracking' => $tracking,
                'name' => '',
                'items' => $built['items'],
                'courier' => 'SPX',
                'complete' => $built['complete'] && $built['items'] !== '',
                'missing_fields' => $missing,
                'order_no' => $gcell('order_sn', $r),
                'product_info' => $productInfo,
            ];
        }

        $spreadsheet->disconnectWorksheets();
    }

    if ($rows === []) {
        throw new RuntimeException('No orders found in packing list.');
    }

    return $rows;
}

/**
 * @param list<string> $pdfPaths
 * @return array<string, array{name: string, items: string}>
 */
function shopee_spx_pdf_rows_by_tracking(array $pdfPaths): array
{
    if ($pdfPaths === []) {
        return [];
    }

    require_once __DIR__ . '/pdf_to_excel.php';
    $extract = pdf_to_excel_extract($pdfPaths);
    $map = [];

    foreach ($extract['files'] as $file) {
        if (!is_array($file['rows'] ?? null)) {
            continue;
        }
        foreach ($file['rows'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tn = trim((string) ($row['tracking'] ?? ''));
            if ($tn === '') {
                continue;
            }
            $key = online_orders_normalize_tracking($tn);
            if ($key === '') {
                continue;
            }
            $map[$key] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'items' => trim((string) ($row['items'] ?? '')),
            ];
        }
    }

    return $map;
}

/**
 * @param list<array<string, mixed>> $rows
 * @param array<string, array{name: string, items: string}> $pdfByTracking
 * @return list<array<string, mixed>>
 */
function shopee_spx_merge_pdf_names(array $rows, array $pdfByTracking): array
{
    foreach ($rows as &$row) {
        $key = online_orders_normalize_tracking((string) ($row['tracking'] ?? ''));
        if ($key === '' || !isset($pdfByTracking[$key])) {
            continue;
        }
        $pdf = $pdfByTracking[$key];
        if ($pdf['name'] !== '') {
            $row['name'] = $pdf['name'];
        }
    }
    unset($row);

    return $rows;
}

/**
 * @param list<string> $pdfTokens
 * @return array{rows: list<array<string, mixed>>}
 */
function online_orders_spx_import_packing_list(string $excelToken, array $pdfTokens = []): array
{
    $excelToken = preg_replace('/[^a-f0-9]/', '', strtolower(trim($excelToken)));
    if ($excelToken === '') {
        throw new RuntimeException('excel_token required.');
    }

    $excels = waybill_stamper_resolve_stash_tokens([$excelToken], 'excel');
    $rows = shopee_spx_packing_list_parse_paths($excels);

    if ($pdfTokens !== []) {
        $pdfs = waybill_stamper_resolve_stash_tokens(array_values($pdfTokens), 'pdf');
        $pdfMap = shopee_spx_pdf_rows_by_tracking($pdfs);
        $rows = shopee_spx_merge_pdf_names($rows, $pdfMap);
    }

    return ['rows' => $rows];
}
