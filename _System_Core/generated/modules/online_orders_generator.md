---
title: online_orders_generator
source: includes/online_orders_generator.php
generated: 2026-09-05T11:35:47+02:00
type: module
function_count: 44
---


# online_orders_generator

> Source: `includes/online_orders_generator.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/waybill_stamper.php`
- `includes/order_remarks.php`
- `includes/qr_decode.php`
- `includes/online_order_parcels.php`
- `includes/online_order_parcels.php`

## online_orders_normalize_pipeline {#online_orders_normalize_pipeline}

**Callers (2):**

- `includes/online_orders_generator.php`:49 — `$pipeline = online_orders_normalize_pipeline($pipeline);`
- `includes/online_orders_generator.php`:962 — `$pipelineTag = online_orders_normalize_pipeline($pipelineTag);`

## online_orders_normalize_partner_seller {#online_orders_normalize_partner_seller}

**Callers (4):**

- `includes/historical_orders_import.php`:307 — `return online_orders_normalize_partner_seller($raw);`
- `includes/online_orders_generator.php`:50 — `$seller = online_orders_normalize_partner_seller($partnerSeller);`
- `includes/online_orders_generator.php`:541 — `? online_orders_normalize_partner_seller((string) $line['partner_seller'])`
- `includes/partner_sold_reconcile.php`:293 — `$partnerSeller = online_orders_normalize_partner_seller((string) ($input['partner_seller'] ?? ''));`

## online_orders_require_partner_seller_for_pipeline {#online_orders_require_partner_seller_for_pipeline}

**Callers (1):**

- `includes/online_orders_generator.php`:966 — `$partnerSeller = online_orders_require_partner_seller_for_pipeline($pipelineTag, $partnerSeller);`

## online_orders_coord {#online_orders_coord}

**Callers (19):**

- `includes/online_orders_generator.php`:155 — `$v = $sheet->getCell(online_orders_coord($c, $r))->getValue();`
- `includes/online_orders_generator.php`:191 — `$v = $sheet->getCell(online_orders_coord($col, $r))->getValue();`
- `includes/online_orders_generator.php`:1080 — `$sheet->setCellValue(online_orders_coord($col, $headerRow), $text);`
- `includes/online_orders_generator.php`:1081 — `$sheet->getStyle(online_orders_coord($col, $headerRow))->getFont()->setBold(true);`
- `includes/online_orders_generator.php`:1088 — `$sheet->setCellValue(online_orders_coord(2, $r), $orderNo !== null && $orderNo !== '' ? (string) $orderNo : '');`
- `includes/online_orders_generator.php`:1089 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['name'] ?? ''));`
- `includes/online_orders_generator.php`:1090 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:1092 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:1093 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));`
- `includes/online_orders_generator.php`:1094 — `$sheet->getCell(online_orders_coord(8, $r))`
- `includes/online_orders_generator.php`:1192 — `$coord = online_orders_coord($col, $headerRow);`
- `includes/online_orders_generator.php`:1206 — `$sheet->setCellValue(online_orders_coord(1, $r), $seq++);`
- `includes/online_orders_generator.php`:1207 — `$sheet->setCellValue(online_orders_coord(2, $r), (string) ($row['order_no'] ?? ''));`
- `includes/online_orders_generator.php`:1208 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['recipient_name'] ?? ''));`
- `includes/online_orders_generator.php`:1209 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:1211 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:1212 — `$sheet->setCellValue(online_orders_coord(6, $r), '');`
- `includes/online_orders_generator.php`:1213 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));`
- `includes/online_orders_generator.php`:1214 — `$sheet->getCell(online_orders_coord(8, $r))`

## online_orders_storage_root {#online_orders_storage_root}

**Callers (2):**

- `includes/online_orders_generator.php`:1099 — `$dir = online_orders_storage_root() . '/work';`
- `includes/online_orders_generator.php`:1224 — `$path = online_orders_storage_root() . '/outputs/master_' . bin2hex(random_bytes(8)) . '.xlsx';`

## online_orders_user_can_access {#online_orders_user_can_access}

**Callers (2):**

- `api/v1/online_orders.php`:25 — `if (!online_orders_user_can_access($user)) {`
- `includes/online_orders_generator.php`:87 — `if (!online_orders_user_can_access($user)) {`

## online_orders_require_access {#online_orders_require_access}

**Callers (1):**

- `includes/online_orders_generator.php`:1253 — `online_orders_require_access();`

## online_orders_ensure_schema {#online_orders_ensure_schema}

**Tables:** `csr_online_batches`

**Callers (3):**

- `includes/online_orders_generator.php`:230 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:253 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:306 — `online_orders_ensure_schema($pdo);`

## online_orders_parse_ipyx_excel_paths {#online_orders_parse_ipyx_excel_paths}

**Callers (2):**

- `includes/online_orders_generator.php`:620 — `$parsed = online_orders_parse_ipyx_excel_paths($paths);`
- `includes/online_orders_generator.php`:980 — `$parsed = online_orders_parse_ipyx_excel_paths($excels);`

## online_orders_batch_start {#online_orders_batch_start}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:87 — `$batch = online_orders_batch_start($pdo, (int) $user['id']);`

## online_orders_batch_get {#online_orders_batch_get}

**Tables:** `csr_online_batches`, `csr_online_batch_runs`, `csr_online_order_lines`

**Callers (3):**

- `api/v1/online_orders.php`:103 — `$batch = online_orders_batch_get($pdo, (int) $user['id'], $batchId > 0 ? $batchId : null);`
- `includes/online_orders_generator.php`:245 — `return online_orders_batch_get($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:1298 — `$batch = online_orders_batch_get($pdo, $userId, $batchId);`

## online_orders_batch_cancel {#online_orders_batch_cancel}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:124 — `online_orders_batch_cancel($pdo, (int) $user['id'], $batchId);`

## online_orders_assert_active_batch {#online_orders_assert_active_batch}

**Tables:** `csr_online_batches`

**Callers (4):**

- `api/v1/online_orders.php`:172 — `online_orders_assert_active_batch($pdo, (int) $user['id'], $batchId);`
- `includes/online_orders_generator.php`:968 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:1125 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:1282 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`

## online_orders_run_start {#online_orders_run_start}

**Tables:** `csr_online_batch_runs`

**Callers (2):**

- `api/v1/online_orders.php`:173 — `$runId = online_orders_run_start($pdo, $batchId, $pipeline);`
- `includes/online_orders_generator.php`:997 — `$runId = online_orders_run_start($pdo, $batchId, $pipelineTag);`

## online_orders_run_complete {#online_orders_run_complete}

**Tables:** `csr_online_batch_runs`

**Callers (2):**

- `api/v1/online_orders.php`:180 — `online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'source' => 'manual_append']);`
- `includes/online_orders_generator.php`:1013 — `online_orders_run_complete($pdo, $runId, $meta);`

## __construct {#__construct}

**Callers (2):**

- `includes/online_orders_generator.php`:349 — `parent::__construct($message ?? 'One or more tracking numbers already exist.');`
- `includes/rts_case_handlers.php`:25 — `parent::__construct($message);`

## online_orders_normalize_tracking {#online_orders_normalize_tracking}

**Callers (7):**

- `includes/online_orders_generator.php`:205 — `$key = online_orders_normalize_tracking($tn);`
- `includes/online_orders_generator.php`:396 — `$key = online_orders_normalize_tracking((string) $row['tracking_number']);`
- `includes/online_orders_generator.php`:424 — `$key = online_orders_normalize_tracking((string) $row['tracking_number']);`
- `includes/online_orders_generator.php`:461 — `$key = online_orders_normalize_tracking($rawTn);`
- `includes/online_orders_generator.php`:536 — `$tn = online_orders_normalize_tracking($rawTn);`
- `includes/shopee_spx_packing_list.php`:160 — `$key = online_orders_normalize_tracking($tn);`
- `includes/shopee_spx_packing_list.php`:182 — `$key = online_orders_normalize_tracking((string) ($row['tracking'] ?? ''));`

## online_orders_ocr_confusables_for_match {#online_orders_ocr_confusables_for_match}

**Callers (1):**

- `includes/online_orders_generator.php`:363 — `return online_orders_ocr_confusables_for_match($t);`

## online_orders_existing_tracking_map {#online_orders_existing_tracking_map}

**Tables:** `online_order_parcels`, `csr_online_order_lines`, `csr_online_batches`

**Callers (1):**

- `includes/online_orders_generator.php`:451 — `$map = online_orders_existing_tracking_map($pdo);`

## online_orders_validate_tracking_duplicates {#online_orders_validate_tracking_duplicates}

**Callers (1):**

- `includes/online_orders_generator.php`:504 — `$conflicts = online_orders_validate_tracking_duplicates($pdo, $pipeline, $lines);`

## online_orders_assert_no_duplicate_tracking {#online_orders_assert_no_duplicate_tracking}

**Callers (3):**

- `includes/online_orders_generator.php`:522 — `online_orders_assert_no_duplicate_tracking($pdo, $pipeline, $lines);`
- `includes/online_orders_generator.php`:930 — `online_orders_assert_no_duplicate_tracking($pdo, 'vip', $matchedRows);`
- `includes/online_orders_generator.php`:995 — `online_orders_assert_no_duplicate_tracking($pdo, $pipelineTag, $lines);`

## online_orders_lines_append {#online_orders_lines_append}

**Tables:** `csr_online_order_lines`

**Callers (2):**

- `api/v1/online_orders.php`:175 — `$added = online_orders_lines_append($pdo, $batchId, $runId, $pipeline, $lines);`
- `includes/online_orders_generator.php`:1000 — `$added = online_orders_lines_append($pdo, $batchId, $runId, $pipelineTag, $lines, $partnerSeller);`

## online_orders_normalize_items_display {#online_orders_normalize_items_display}

**Callers (9):**

- `api/v1/parcels.php`:165 — `$items = online_orders_normalize_items_display($items);`
- `includes/historical_orders_import.php`:315 — `$raw = online_orders_normalize_items_display(trim($remarks));`
- `includes/online_order_parcels.php`:943 — `$itemsRaw = online_orders_normalize_items_display(trim($itemsRaw));`
- `includes/online_orders_generator.php`:549 — `online_orders_normalize_items_display($items),`
- `includes/online_orders_generator.php`:590 — `$items = online_orders_normalize_items_display((string) ($line['items'] ?? ''));`
- `includes/online_orders_generator.php`:630 — `'items' => online_orders_normalize_items_display($row['items']),`
- `includes/online_orders_generator.php`:653 — `$items = online_orders_normalize_items_display(trim((string) ($row['items'] ?? '')));`
- `includes/online_orders_generator.php`:700 — `$items = online_orders_normalize_items_display(trim((string) ($row['items'] ?? '')));`
- `includes/online_orders_generator.php`:770 — `'items' => online_orders_normalize_items_display($items),`

## online_orders_normalize_line_payloads {#online_orders_normalize_line_payloads}

**Callers (1):**

- `includes/online_orders_generator.php`:981 — `$lines = online_orders_normalize_line_payloads(array_map(static function (array $row): array {`

## online_orders_tool_excel_register {#online_orders_tool_excel_register}

**Callers (1):**

- `api/v1/online_orders.php`:320 — `$result = online_orders_tool_excel_register($excelToken !== '' ? $excelToken : $excelTokens);`

## online_orders_tool_spx_prepare {#online_orders_tool_spx_prepare}

**Callers (2):**

- `api/v1/online_orders.php`:299 — `$result = online_orders_tool_spx_prepare($rows);`
- `includes/online_orders_generator.php`:1126 — `$prep = online_orders_tool_spx_prepare($rows);`

## online_orders_tool_vip_prepare {#online_orders_tool_vip_prepare}

**Callers (2):**

- `api/v1/online_orders.php`:200 — `$result = online_orders_tool_vip_prepare($rows);`
- `includes/online_orders_generator.php`:932 — `$prep = online_orders_tool_vip_prepare($matchedRows);`

## online_orders_vip_arranger_stash_prune {#online_orders_vip_arranger_stash_prune}

**Callers (3):**

- `includes/online_orders_generator.php`:757 — `online_orders_vip_arranger_stash_prune();`
- `includes/online_orders_generator.php`:802 — `online_orders_vip_arranger_stash_prune();`
- `includes/online_orders_generator.php`:826 — `online_orders_vip_arranger_stash_prune();`

## online_orders_vip_arranger_stash_save {#online_orders_vip_arranger_stash_save}

**Callers (1):**

- `api/v1/online_orders.php`:220 — `$result = online_orders_vip_arranger_stash_save($rows);`

## online_orders_vip_arranger_stash_get {#online_orders_vip_arranger_stash_get}

**Callers (3):**

- `api/v1/online_orders.php`:242 — `$stash = online_orders_vip_arranger_stash_get($token);`
- `includes/online_orders_generator.php`:833 — `online_orders_vip_arranger_stash_get($token);`
- `includes/online_orders_generator.php`:868 — `$arranger = online_orders_vip_arranger_stash_get($arrangerToken);`

## online_orders_vip_arranger_latest_token {#online_orders_vip_arranger_latest_token}

**Callers (2):**

- `api/v1/online_orders.php`:234 — `$token = online_orders_vip_arranger_latest_token() ?? '';`
- `api/v1/online_orders.php`:266 — `$arrangerToken = online_orders_vip_arranger_latest_token() ?? '';`

## online_orders_vip_courier_label {#online_orders_vip_courier_label}

**Callers (2):**

- `includes/online_orders_generator.php`:893 — `$courierLabel = online_orders_vip_courier_label($profileId, null);`
- `includes/online_orders_generator.php`:915 — `'courier' => online_orders_vip_courier_label(`

## online_orders_tool_vip_merge {#online_orders_tool_vip_merge}

**Callers (1):**

- `api/v1/online_orders.php`:273 — `$result = online_orders_tool_vip_merge(`

## online_orders_run_unified_stamp {#online_orders_run_unified_stamp}

**Tables:** `csr_online_batch_runs`

**Callers (4):**

- `api/v1/online_orders.php`:352 — `$result = online_orders_run_unified_stamp(`
- `includes/online_orders_generator.php`:1038 — `return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelTokens, 'partner');`
- `includes/online_orders_generator.php`:1052 — `return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelToken, 'vip');`
- `includes/online_orders_generator.php`:1149 — `return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelToken, 'shopee_spx');`

## online_orders_run_partner_stamp {#online_orders_run_partner_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:385 — `$result = online_orders_run_partner_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelTokens);`

## online_orders_run_vip_stamp {#online_orders_run_vip_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:410 — `$result = online_orders_run_vip_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_build_ipyx_excel_from_rows {#online_orders_build_ipyx_excel_from_rows}

**Callers (2):**

- `includes/online_orders_generator.php`:671 — `$excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'Shopee SPX — Online Orders');`
- `includes/online_orders_generator.php`:717 — `$excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'VIP — Online Orders');`

## online_orders_run_shopee_resolve {#online_orders_run_shopee_resolve}

**Callers (1):**

- `api/v1/online_orders.php`:433 — `$result = online_orders_run_shopee_resolve($pdo, (int) $user['id'], $batchId, $rows);`

## online_orders_run_shopee_stamp {#online_orders_run_shopee_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:458 — `$result = online_orders_run_shopee_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_merge_master_workbook {#online_orders_merge_master_workbook}

**Tables:** `csr_online_order_lines`

**Callers (1):**

- `includes/online_orders_generator.php`:1286 — `$metaJson = online_orders_merge_master_workbook($pdo, $batchId);`

## online_orders_build_print_workbook_meta {#online_orders_build_print_workbook_meta}

**Callers (2):**

- `includes/online_order_parcels.php`:879 — `$metaJson = online_orders_build_print_workbook_meta($rows);`
- `includes/online_orders_generator.php`:1164 — `return online_orders_build_print_workbook_meta($dbRows);`

## online_orders_register_master_download {#online_orders_register_master_download}

**Callers (2):**

- `includes/online_order_parcels.php`:880 — `$token = online_orders_register_master_download($metaJson);`
- `includes/online_orders_generator.php`:1289 — `$token = online_orders_register_master_download($metaJson);`

## online_orders_serve_download {#online_orders_serve_download}

**Callers (2):**

- `api/v1/online_orders.php`:13 — `online_orders_serve_download(trim((string) ($_GET['token'] ?? '')));`
- `api/v1/parcels.php`:98 — `online_orders_serve_download($token);`

## online_orders_batch_finalize {#online_orders_batch_finalize}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:145 — `$result = online_orders_batch_finalize($pdo, (int) $user['id'], $batchId);`

