---
title: online_orders_generator
source: includes/online_orders_generator.php
generated: 2026-06-25T08:52:42+02:00
type: module
function_count: 38
---


# online_orders_generator

> Source: `includes/online_orders_generator.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/waybill_stamper.php`
- `includes/qr_decode.php`
- `includes/online_order_parcels.php`
- `includes/online_order_parcels.php`

## online_orders_normalize_pipeline {#online_orders_normalize_pipeline}

**Callers (2):**

- `includes/online_orders_generator.php`:48 — `$pipeline = online_orders_normalize_pipeline($pipeline);`
- `includes/online_orders_generator.php`:752 — `$pipelineTag = online_orders_normalize_pipeline($pipelineTag);`

## online_orders_normalize_partner_seller {#online_orders_normalize_partner_seller}

**Callers (2):**

- `includes/online_orders_generator.php`:49 — `$seller = online_orders_normalize_partner_seller($partnerSeller);`
- `includes/online_orders_generator.php`:540 — `? online_orders_normalize_partner_seller((string) $line['partner_seller'])`

## online_orders_require_partner_seller_for_pipeline {#online_orders_require_partner_seller_for_pipeline}

**Callers (1):**

- `includes/online_orders_generator.php`:756 — `$partnerSeller = online_orders_require_partner_seller_for_pipeline($pipelineTag, $partnerSeller);`

## online_orders_coord {#online_orders_coord}

**Callers (19):**

- `includes/online_orders_generator.php`:154 — `$v = $sheet->getCell(online_orders_coord($c, $r))->getValue();`
- `includes/online_orders_generator.php`:190 — `$v = $sheet->getCell(online_orders_coord($col, $r))->getValue();`
- `includes/online_orders_generator.php`:870 — `$sheet->setCellValue(online_orders_coord($col, $headerRow), $text);`
- `includes/online_orders_generator.php`:871 — `$sheet->getStyle(online_orders_coord($col, $headerRow))->getFont()->setBold(true);`
- `includes/online_orders_generator.php`:878 — `$sheet->setCellValue(online_orders_coord(2, $r), $orderNo !== null && $orderNo !== '' ? (string) $orderNo : '');`
- `includes/online_orders_generator.php`:879 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['name'] ?? ''));`
- `includes/online_orders_generator.php`:880 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:882 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:883 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));`
- `includes/online_orders_generator.php`:884 — `$sheet->getCell(online_orders_coord(8, $r))`
- `includes/online_orders_generator.php`:982 — `$coord = online_orders_coord($col, $headerRow);`
- `includes/online_orders_generator.php`:996 — `$sheet->setCellValue(online_orders_coord(1, $r), $seq++);`
- `includes/online_orders_generator.php`:997 — `$sheet->setCellValue(online_orders_coord(2, $r), (string) ($row['order_no'] ?? ''));`
- `includes/online_orders_generator.php`:998 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['recipient_name'] ?? ''));`
- `includes/online_orders_generator.php`:999 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:1001 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:1002 — `$sheet->setCellValue(online_orders_coord(6, $r), '');`
- `includes/online_orders_generator.php`:1003 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));`
- `includes/online_orders_generator.php`:1004 — `$sheet->getCell(online_orders_coord(8, $r))`

## online_orders_storage_root {#online_orders_storage_root}

**Callers (2):**

- `includes/online_orders_generator.php`:889 — `$dir = online_orders_storage_root() . '/work';`
- `includes/online_orders_generator.php`:1014 — `$path = online_orders_storage_root() . '/outputs/master_' . bin2hex(random_bytes(8)) . '.xlsx';`

## online_orders_user_can_access {#online_orders_user_can_access}

**Callers (2):**

- `api/v1/online_orders.php`:25 — `if (!online_orders_user_can_access($user)) {`
- `includes/online_orders_generator.php`:86 — `if (!online_orders_user_can_access($user)) {`

## online_orders_require_access {#online_orders_require_access}

**Callers (1):**

- `includes/online_orders_generator.php`:1043 — `online_orders_require_access();`

## online_orders_ensure_schema {#online_orders_ensure_schema}

**Tables:** `csr_online_batches`

**Callers (3):**

- `includes/online_orders_generator.php`:229 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:252 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:305 — `online_orders_ensure_schema($pdo);`

## online_orders_parse_ipyx_excel_paths {#online_orders_parse_ipyx_excel_paths}

**Callers (2):**

- `includes/online_orders_generator.php`:619 — `$parsed = online_orders_parse_ipyx_excel_paths($paths);`
- `includes/online_orders_generator.php`:770 — `$parsed = online_orders_parse_ipyx_excel_paths($excels);`

## online_orders_batch_start {#online_orders_batch_start}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:84 — `$batch = online_orders_batch_start($pdo, (int) $user['id']);`

## online_orders_batch_get {#online_orders_batch_get}

**Tables:** `csr_online_batches`, `csr_online_batch_runs`, `csr_online_order_lines`

**Callers (3):**

- `api/v1/online_orders.php`:100 — `$batch = online_orders_batch_get($pdo, (int) $user['id'], $batchId > 0 ? $batchId : null);`
- `includes/online_orders_generator.php`:244 — `return online_orders_batch_get($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:1088 — `$batch = online_orders_batch_get($pdo, $userId, $batchId);`

## online_orders_batch_cancel {#online_orders_batch_cancel}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:121 — `online_orders_batch_cancel($pdo, (int) $user['id'], $batchId);`

## online_orders_assert_active_batch {#online_orders_assert_active_batch}

**Tables:** `csr_online_batches`

**Callers (4):**

- `api/v1/online_orders.php`:169 — `online_orders_assert_active_batch($pdo, (int) $user['id'], $batchId);`
- `includes/online_orders_generator.php`:758 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:915 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:1072 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`

## online_orders_run_start {#online_orders_run_start}

**Tables:** `csr_online_batch_runs`

**Callers (2):**

- `api/v1/online_orders.php`:170 — `$runId = online_orders_run_start($pdo, $batchId, $pipeline);`
- `includes/online_orders_generator.php`:787 — `$runId = online_orders_run_start($pdo, $batchId, $pipelineTag);`

## online_orders_run_complete {#online_orders_run_complete}

**Tables:** `csr_online_batch_runs`

**Callers (2):**

- `api/v1/online_orders.php`:177 — `online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'source' => 'manual_append']);`
- `includes/online_orders_generator.php`:803 — `online_orders_run_complete($pdo, $runId, $meta);`

## __construct {#__construct}

**Callers (2):**

- `includes/online_orders_generator.php`:348 — `parent::__construct($message ?? 'One or more tracking numbers already exist.');`
- `includes/rts_case_handlers.php`:23 — `parent::__construct($message);`

## online_orders_normalize_tracking {#online_orders_normalize_tracking}

**Callers (7):**

- `includes/online_orders_generator.php`:204 — `$key = online_orders_normalize_tracking($tn);`
- `includes/online_orders_generator.php`:395 — `$key = online_orders_normalize_tracking((string) $row['tracking_number']);`
- `includes/online_orders_generator.php`:423 — `$key = online_orders_normalize_tracking((string) $row['tracking_number']);`
- `includes/online_orders_generator.php`:460 — `$key = online_orders_normalize_tracking($rawTn);`
- `includes/online_orders_generator.php`:535 — `$tn = online_orders_normalize_tracking($rawTn);`
- `includes/shopee_spx_packing_list.php`:160 — `$key = online_orders_normalize_tracking($tn);`
- `includes/shopee_spx_packing_list.php`:182 — `$key = online_orders_normalize_tracking((string) ($row['tracking'] ?? ''));`

## online_orders_ocr_confusables_for_match {#online_orders_ocr_confusables_for_match}

**Callers (1):**

- `includes/online_orders_generator.php`:362 — `return online_orders_ocr_confusables_for_match($t);`

## online_orders_existing_tracking_map {#online_orders_existing_tracking_map}

**Tables:** `online_order_parcels`, `csr_online_order_lines`, `csr_online_batches`

**Callers (1):**

- `includes/online_orders_generator.php`:450 — `$map = online_orders_existing_tracking_map($pdo);`

## online_orders_validate_tracking_duplicates {#online_orders_validate_tracking_duplicates}

**Callers (1):**

- `includes/online_orders_generator.php`:503 — `$conflicts = online_orders_validate_tracking_duplicates($pdo, $pipeline, $lines);`

## online_orders_assert_no_duplicate_tracking {#online_orders_assert_no_duplicate_tracking}

**Callers (2):**

- `includes/online_orders_generator.php`:521 — `online_orders_assert_no_duplicate_tracking($pdo, $pipeline, $lines);`
- `includes/online_orders_generator.php`:785 — `online_orders_assert_no_duplicate_tracking($pdo, $pipelineTag, $lines);`

## online_orders_lines_append {#online_orders_lines_append}

**Tables:** `csr_online_order_lines`

**Callers (2):**

- `api/v1/online_orders.php`:172 — `$added = online_orders_lines_append($pdo, $batchId, $runId, $pipeline, $lines);`
- `includes/online_orders_generator.php`:790 — `$added = online_orders_lines_append($pdo, $batchId, $runId, $pipelineTag, $lines, $partnerSeller);`

## online_orders_normalize_items_display {#online_orders_normalize_items_display}

**Callers (7):**

- `api/v1/parcels.php`:166 — `$items = online_orders_normalize_items_display($items);`
- `includes/online_order_parcels.php`:893 — `$itemsRaw = online_orders_normalize_items_display(trim($itemsRaw));`
- `includes/online_orders_generator.php`:548 — `online_orders_normalize_items_display($items),`
- `includes/online_orders_generator.php`:589 — `$items = online_orders_normalize_items_display((string) ($line['items'] ?? ''));`
- `includes/online_orders_generator.php`:629 — `'items' => online_orders_normalize_items_display($row['items']),`
- `includes/online_orders_generator.php`:652 — `$items = online_orders_normalize_items_display(trim((string) ($row['items'] ?? '')));`
- `includes/online_orders_generator.php`:699 — `$items = online_orders_normalize_items_display(trim((string) ($row['items'] ?? '')));`

## online_orders_normalize_line_payloads {#online_orders_normalize_line_payloads}

**Callers (1):**

- `includes/online_orders_generator.php`:771 — `$lines = online_orders_normalize_line_payloads(array_map(static function (array $row): array {`

## online_orders_tool_excel_register {#online_orders_tool_excel_register}

**Callers (1):**

- `api/v1/online_orders.php`:238 — `$result = online_orders_tool_excel_register($excelToken !== '' ? $excelToken : $excelTokens);`

## online_orders_tool_spx_prepare {#online_orders_tool_spx_prepare}

**Callers (2):**

- `api/v1/online_orders.php`:217 — `$result = online_orders_tool_spx_prepare($rows);`
- `includes/online_orders_generator.php`:916 — `$prep = online_orders_tool_spx_prepare($rows);`

## online_orders_tool_vip_prepare {#online_orders_tool_vip_prepare}

**Callers (1):**

- `api/v1/online_orders.php`:197 — `$result = online_orders_tool_vip_prepare($rows);`

## online_orders_run_unified_stamp {#online_orders_run_unified_stamp}

**Tables:** `csr_online_batch_runs`

**Callers (4):**

- `api/v1/online_orders.php`:270 — `$result = online_orders_run_unified_stamp(`
- `includes/online_orders_generator.php`:828 — `return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelTokens, 'partner');`
- `includes/online_orders_generator.php`:842 — `return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelToken, 'vip');`
- `includes/online_orders_generator.php`:939 — `return online_orders_run_unified_stamp($pdo, $userId, $batchId, $pdfTokens, $excelToken, 'shopee_spx');`

## online_orders_run_partner_stamp {#online_orders_run_partner_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:303 — `$result = online_orders_run_partner_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelTokens);`

## online_orders_run_vip_stamp {#online_orders_run_vip_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:328 — `$result = online_orders_run_vip_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_build_ipyx_excel_from_rows {#online_orders_build_ipyx_excel_from_rows}

**Callers (2):**

- `includes/online_orders_generator.php`:670 — `$excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'Shopee SPX — Online Orders');`
- `includes/online_orders_generator.php`:716 — `$excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'VIP — Online Orders');`

## online_orders_run_shopee_resolve {#online_orders_run_shopee_resolve}

**Callers (1):**

- `api/v1/online_orders.php`:351 — `$result = online_orders_run_shopee_resolve($pdo, (int) $user['id'], $batchId, $rows);`

## online_orders_run_shopee_stamp {#online_orders_run_shopee_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:376 — `$result = online_orders_run_shopee_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_merge_master_workbook {#online_orders_merge_master_workbook}

**Tables:** `csr_online_order_lines`

**Callers (1):**

- `includes/online_orders_generator.php`:1076 — `$metaJson = online_orders_merge_master_workbook($pdo, $batchId);`

## online_orders_build_print_workbook_meta {#online_orders_build_print_workbook_meta}

**Callers (2):**

- `includes/online_order_parcels.php`:829 — `$metaJson = online_orders_build_print_workbook_meta($rows);`
- `includes/online_orders_generator.php`:954 — `return online_orders_build_print_workbook_meta($dbRows);`

## online_orders_register_master_download {#online_orders_register_master_download}

**Callers (2):**

- `includes/online_order_parcels.php`:830 — `$token = online_orders_register_master_download($metaJson);`
- `includes/online_orders_generator.php`:1079 — `$token = online_orders_register_master_download($metaJson);`

## online_orders_serve_download {#online_orders_serve_download}

**Callers (2):**

- `api/v1/online_orders.php`:13 — `online_orders_serve_download(trim((string) ($_GET['token'] ?? '')));`
- `api/v1/parcels.php`:98 — `online_orders_serve_download($token);`

## online_orders_batch_finalize {#online_orders_batch_finalize}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:142 — `$result = online_orders_batch_finalize($pdo, (int) $user['id'], $batchId);`

