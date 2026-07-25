---
title: sp_sort_service
source: includes/sp_sort_service.php
generated: 2026-07-25T10:56:58+02:00
type: module
function_count: 26
---


# sp_sort_service

> Source: `includes/sp_sort_service.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/rts_storage.php`
- `includes/rts_storage.php`

## sp_sort_storage_dir {#sp_sort_storage_dir}

**Callers (4):**

- `includes/sp_sort_service.php`:591 — `$outPath = sp_sort_storage_dir() . '/' . $filename;`
- `includes/sp_sort_service.php`:712 — `$outPath = sp_sort_storage_dir() . '/' . $filename;`
- `sp_sort/api/export.php`:48 — `$path = sp_sort_storage_dir() . '/' . $filename;`
- `sp_sort/download.php`:24 — `$path = sp_sort_storage_dir() . '/' . $filename;`

## sp_sort_upload_dir {#sp_sort_upload_dir}

**Callers (1):**

- `sp_sort/process.php`:35 — `$dest = sp_sort_upload_dir() . '/' . uniqid('up_', true) . '.' . $ext;`

## sp_sort_numeric_d {#sp_sort_numeric_d}

**Callers (1):**

- `includes/sp_sort_service.php`:64 — `return [sp_sort_numeric_d($item), strtolower($item)];`

## sp_sort_item_sort_key {#sp_sort_item_sort_key}

**Callers (1):**

- `includes/sp_sort_service.php`:77 — `[$qty, $itemText] = sp_sort_item_sort_key($row);`

## sp_sort_rows_by_column_d {#sp_sort_rows_by_column_d}

**Callers (1):**

- `includes/sp_sort_service.php`:580 — `$sorted = sp_sort_rows_by_column_d($dataRows, $sortDir);`

## sp_sort_analyze_row {#sp_sort_analyze_row}

**Callers (2):**

- `includes/sp_sort_service.php`:525 — `$rowMeta = $metaPerRow[$i] ?? sp_sort_analyze_row($norm);`
- `includes/sp_sort_service.php`:585 — `$meta = sp_sort_analyze_row($norm);`

## sp_sort_excel_cell_plain {#sp_sort_excel_cell_plain}

**Callers (10):**

- `includes/sp_sort_service.php`:195 — `$rows[$idx][1] = sp_sort_excel_cell_plain($formatted);`
- `includes/sp_sort_service.php`:197 — `$rows[$idx][1] = sp_sort_excel_cell_plain($rows[$idx][1]);`
- `includes/sp_sort_service.php`:325 — `sp_sort_excel_cell_plain($row[1] ?? ''),`
- `includes/sp_sort_service.php`:326 — `sp_sort_excel_cell_plain($row[2] ?? ''),`
- `includes/sp_sort_service.php`:327 — `sp_sort_excel_cell_plain($row[3] ?? ''),`
- `includes/sp_sort_service.php`:328 — `sp_sort_excel_cell_plain($row[4] ?? ''),`
- `includes/sp_sort_service.php`:334 — `sp_sort_excel_cell_plain($row[0] ?? ''),`
- `includes/sp_sort_service.php`:335 — `sp_sort_excel_cell_plain($row[1] ?? ''),`
- `includes/sp_sort_service.php`:336 — `sp_sort_excel_cell_plain($row[2] ?? ''),`
- `includes/sp_sort_service.php`:337 — `sp_sort_excel_cell_plain($row[3] ?? ''),`

## sp_sort_read_sheet_rows {#sp_sort_read_sheet_rows}

**Callers (1):**

- `includes/sp_sort_service.php`:448 — `$rows = sp_sort_read_sheet_rows($sheet);`

## sp_sort_row_is_empty {#sp_sort_row_is_empty}

**Callers (1):**

- `includes/sp_sort_service.php`:235 — `if (sp_sort_row_is_empty($row) || sp_sort_is_prepared_by_row($row)) {`

## sp_sort_is_pyx_header_row {#sp_sort_is_pyx_header_row}

**Callers (2):**

- `includes/sp_sort_service.php`:230 — `return sp_sort_is_pyx_header_row($row);`
- `includes/sp_sort_service.php`:370 — `if (count($rows) >= 4 && sp_sort_is_pyx_header_row($rows[3])) {`

## sp_sort_is_simple_header_row {#sp_sort_is_simple_header_row}

**Callers (1):**

- `includes/sp_sort_service.php`:351 — `if (count($rows) >= 2 && sp_sort_is_simple_header_row($rows[0]) && !str_contains(strtolower(trim((string) ($rows[0][1] ?? ''))), 'ipyx')) {`

## sp_sort_is_skippable_data_row {#sp_sort_is_skippable_data_row}

**Callers (3):**

- `includes/sp_sort_service.php`:355 — `if (sp_sort_is_skippable_data_row($row)) {`
- `includes/sp_sort_service.php`:391 — `if (sp_sort_is_skippable_data_row($row)) {`
- `includes/sp_sort_service.php`:415 — `if (sp_sort_is_skippable_data_row($row)) {`

## sp_sort_set_order_cell {#sp_sort_set_order_cell}

**Callers (1):**

- `includes/sp_sort_service.php`:528 — `sp_sort_set_order_cell($sheet, 'B' . $rn, $norm[1]);`

## sp_sort_is_prepared_by_row {#sp_sort_is_prepared_by_row}

**Callers (1):**

- `includes/sp_sort_service.php`:235 — `if (sp_sort_row_is_empty($row) || sp_sort_is_prepared_by_row($row)) {`

## sp_sort_default_print_meta {#sp_sort_default_print_meta}

**Callers (9):**

- `includes/sp_sort_service.php`:299 — `$meta = sp_sort_default_print_meta();`
- `includes/sp_sort_service.php`:348 — `return ['format' => 'generic', 'meta' => sp_sort_default_print_meta(), 'header' => null, 'rows' => [], 'error' => 'Empty sheet.'];`
- `includes/sp_sort_service.php`:363 — `'meta' => sp_sort_default_print_meta(),`
- `includes/sp_sort_service.php`:371 — `$meta = sp_sort_default_print_meta();`
- `includes/sp_sort_service.php`:423 — `'meta' => sp_sort_default_print_meta(),`
- `includes/sp_sort_service.php`:438 — `$meta = sp_sort_default_print_meta();`
- `includes/sp_sort_service.php`:579 — `$printMeta = $merged['meta'] ?? sp_sort_default_print_meta();`
- `sp_sort/print.php`:17 — `$printMeta = sp_sort_default_print_meta();`
- `sp_sort/workflow.php`:22 — `$printMeta = sp_sort_default_print_meta();`

## sp_sort_resolve_print_meta {#sp_sort_resolve_print_meta}

**Callers (4):**

- `includes/sp_sort_service.php`:489 — `$meta = sp_sort_resolve_print_meta($printMeta);`
- `includes/sp_sort_service.php`:701 — `$printMeta = sp_sort_resolve_print_meta(array_merge($storedMeta, $printMetaOverrides));`
- `sp_sort/print.php`:19 — `$printMeta = sp_sort_resolve_print_meta($printMeta);`
- `sp_sort/workflow.php`:24 — `$printMeta = sp_sort_resolve_print_meta($printMeta);`

## sp_sort_normalize_pyx_row {#sp_sort_normalize_pyx_row}

**Callers (10):**

- `includes/sp_sort_service.php`:61 — `$norm = sp_sort_normalize_pyx_row($row);`
- `includes/sp_sort_service.php`:238 — `$norm = sp_sort_normalize_pyx_row($row);`
- `includes/sp_sort_service.php`:358 — `$dataRows[] = sp_sort_normalize_pyx_row($row);`
- `includes/sp_sort_service.php`:394 — `$dataRows[] = sp_sort_normalize_pyx_row($row);`
- `includes/sp_sort_service.php`:418 — `$dataRows[] = sp_sort_normalize_pyx_row($row);`
- `includes/sp_sort_service.php`:524 — `$norm = sp_sort_normalize_pyx_row($rowData);`
- `includes/sp_sort_service.php`:584 — `$norm = sp_sort_normalize_pyx_row($row);`
- `includes/sp_sort_service.php`:638 — `json_encode(array_values(sp_sort_normalize_pyx_row($row)), JSON_UNESCAPED_UNICODE),`
- `includes/sp_sort_service.php`:669 — `$norm = sp_sort_normalize_pyx_row($cells);`
- `sp_sort/print.php`:26 — `$norm = sp_sort_normalize_pyx_row($item['row']);`

## sp_sort_parse_sheet_rows {#sp_sort_parse_sheet_rows}

**Callers (1):**

- `includes/sp_sort_service.php`:452 — `$parsed = sp_sort_parse_sheet_rows($rows, $hasHeader);`

## sp_sort_merge_uploads {#sp_sort_merge_uploads}

**Callers (1):**

- `sp_sort/process.php`:55 — `$merged = sp_sort_merge_uploads($uploadedPaths, $hasHeader);`

## sp_sort_write_print_excel {#sp_sort_write_print_excel}

**Callers (2):**

- `includes/sp_sort_service.php`:592 — `sp_sort_write_print_excel($sorted, $metaList, $outPath, $printMeta, '');`
- `includes/sp_sort_service.php`:713 — `sp_sort_write_print_excel($dataRows, $metaList, $outPath, $printMeta, $preparedBy);`

## sp_sort_create_batch {#sp_sort_create_batch}

**Tables:** `sp_sort_batches`, `sp_sort_rows`

**Callers (1):**

- `sp_sort/process.php`:60 — `$batchId = sp_sort_create_batch(`

## sp_sort_load_batch_rows {#sp_sort_load_batch_rows}

**Tables:** `sp_sort_rows`

**Callers (2):**

- `includes/sp_sort_service.php`:704 — `$loaded = sp_sort_load_batch_rows($pdo, $batchId);`
- `sp_sort/print.php`:23 — `$loaded = sp_sort_load_batch_rows($pdo, $batchId);`

## sp_sort_regenerate_batch_file {#sp_sort_regenerate_batch_file}

**Tables:** `sp_sort_batches`

**Callers (1):**

- `sp_sort/api/export.php`:33 — `sp_sort_regenerate_batch_file($pdo, $batchId, $preparedBy, $printMetaOverrides);`

## sp_sort_shop_counts {#sp_sort_shop_counts}

**Tables:** `sp_sort_rows`, `sp_sort_batches`

**Callers (1):**

- `dashboard.php`:129 — `$deliveredByShop = sp_sort_shop_counts($pdo);`

## sp_sort_tables_exist {#sp_sort_tables_exist}

**Tables:** `sp_sort_batches`

**Callers (1):**

- `dashboard.php`:123 — `$spSortReady = sp_sort_tables_exist($pdo);`

## sp_sort_courier_counts {#sp_sort_courier_counts}

**Tables:** `sp_sort_rows`, `sp_sort_batches`

**Callers (1):**

- `dashboard.php`:128 — `$deliveredByCourier = sp_sort_courier_counts($pdo);`

