---
title: online_orders_generator
source: includes/online_orders_generator.php
generated: 2026-06-05T06:53:59+02:00
type: module
function_count: 28
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

## online_orders_coord {#online_orders_coord}

**Callers (19):**

- `includes/online_orders_generator.php`:114 — `$v = $sheet->getCell(online_orders_coord($c, $r))->getValue();`
- `includes/online_orders_generator.php`:150 — `$v = $sheet->getCell(online_orders_coord($col, $r))->getValue();`
- `includes/online_orders_generator.php`:622 — `$sheet->setCellValue(online_orders_coord($col, $headerRow), $text);`
- `includes/online_orders_generator.php`:623 — `$sheet->getStyle(online_orders_coord($col, $headerRow))->getFont()->setBold(true);`
- `includes/online_orders_generator.php`:630 — `$sheet->setCellValue(online_orders_coord(2, $r), $orderNo !== null && $orderNo !== '' ? (string) $orderNo : '');`
- `includes/online_orders_generator.php`:631 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['name'] ?? ''));`
- `includes/online_orders_generator.php`:632 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:634 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:635 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));`
- `includes/online_orders_generator.php`:636 — `$sheet->getCell(online_orders_coord(8, $r))`
- `includes/online_orders_generator.php`:767 — `$coord = online_orders_coord($col, $headerRow);`
- `includes/online_orders_generator.php`:781 — `$sheet->setCellValue(online_orders_coord(1, $r), $seq++);`
- `includes/online_orders_generator.php`:782 — `$sheet->setCellValue(online_orders_coord(2, $r), (string) ($row['order_no'] ?? ''));`
- `includes/online_orders_generator.php`:783 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) $row['recipient_name']);`
- `includes/online_orders_generator.php`:784 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:786 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:787 — `$sheet->setCellValue(online_orders_coord(6, $r), '');`
- `includes/online_orders_generator.php`:788 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) $row['courier']);`
- `includes/online_orders_generator.php`:789 — `$sheet->getCell(online_orders_coord(8, $r))`

## online_orders_storage_root {#online_orders_storage_root}

**Callers (2):**

- `includes/online_orders_generator.php`:641 — `$dir = online_orders_storage_root() . '/work';`
- `includes/online_orders_generator.php`:799 — `$path = online_orders_storage_root() . '/outputs/master_' . bin2hex(random_bytes(8)) . '.xlsx';`

## online_orders_user_can_access {#online_orders_user_can_access}

**Callers (2):**

- `api/v1/online_orders.php`:25 — `if (!online_orders_user_can_access($user)) {`
- `includes/online_orders_generator.php`:46 — `if (!online_orders_user_can_access($user)) {`

## online_orders_require_access {#online_orders_require_access}

**Callers (1):**

- `includes/online_orders_generator.php`:828 — `online_orders_require_access();`

## online_orders_ensure_schema {#online_orders_ensure_schema}

**Tables:** `csr_online_batches`

**Callers (3):**

- `includes/online_orders_generator.php`:189 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:212 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:265 — `online_orders_ensure_schema($pdo);`

## online_orders_parse_ipyx_excel_paths {#online_orders_parse_ipyx_excel_paths}

**Callers (1):**

- `includes/online_orders_generator.php`:529 — `$parsed = online_orders_parse_ipyx_excel_paths($excels);`

## online_orders_batch_start {#online_orders_batch_start}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:80 — `$batch = online_orders_batch_start($pdo, (int) $user['id']);`

## online_orders_batch_get {#online_orders_batch_get}

**Tables:** `csr_online_batches`, `csr_online_batch_runs`, `csr_online_order_lines`

**Callers (3):**

- `api/v1/online_orders.php`:96 — `$batch = online_orders_batch_get($pdo, (int) $user['id'], $batchId > 0 ? $batchId : null);`
- `includes/online_orders_generator.php`:204 — `return online_orders_batch_get($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:873 — `$batch = online_orders_batch_get($pdo, $userId, $batchId);`

## online_orders_batch_cancel {#online_orders_batch_cancel}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:117 — `online_orders_batch_cancel($pdo, (int) $user['id'], $batchId);`

## online_orders_assert_active_batch {#online_orders_assert_active_batch}

**Tables:** `csr_online_batches`

**Callers (6):**

- `api/v1/online_orders.php`:165 — `online_orders_assert_active_batch($pdo, (int) $user['id'], $batchId);`
- `includes/online_orders_generator.php`:523 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:576 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:666 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:719 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:857 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`

## online_orders_run_start {#online_orders_run_start}

**Tables:** `csr_online_batch_runs`

**Callers (4):**

- `api/v1/online_orders.php`:166 — `$runId = online_orders_run_start($pdo, $batchId, $pipeline);`
- `includes/online_orders_generator.php`:542 — `$runId = online_orders_run_start($pdo, $batchId, 'partner');`
- `includes/online_orders_generator.php`:582 — `$runId = online_orders_run_start($pdo, $batchId, 'vip');`
- `includes/online_orders_generator.php`:698 — `$runId = online_orders_run_start($pdo, $batchId, 'shopee_spx');`

## online_orders_run_complete {#online_orders_run_complete}

**Tables:** `csr_online_batch_runs`

**Callers (4):**

- `api/v1/online_orders.php`:173 — `online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'source' => 'manual_append']);`
- `includes/online_orders_generator.php`:546 — `online_orders_run_complete($pdo, $runId, [`
- `includes/online_orders_generator.php`:585 — `online_orders_run_complete($pdo, $runId, ['stamp_matched' => $stamp['matched']]);`
- `includes/online_orders_generator.php`:701 — `online_orders_run_complete($pdo, $runId, ['excel_token' => $excelToken, 'row_count' => count($normalized)]);`

## __construct {#__construct}

**Callers (1):**

- `includes/online_orders_generator.php`:308 — `parent::__construct($message ?? 'One or more tracking numbers already exist.');`

## online_orders_normalize_tracking {#online_orders_normalize_tracking}

**Callers (7):**

- `includes/online_orders_generator.php`:164 — `$key = online_orders_normalize_tracking($tn);`
- `includes/online_orders_generator.php`:351 — `$key = online_orders_normalize_tracking((string) $row['tracking_number']);`
- `includes/online_orders_generator.php`:379 — `$key = online_orders_normalize_tracking((string) $row['tracking_number']);`
- `includes/online_orders_generator.php`:416 — `$key = online_orders_normalize_tracking($rawTn);`
- `includes/online_orders_generator.php`:490 — `$tn = online_orders_normalize_tracking($rawTn);`
- `includes/shopee_spx_packing_list.php`:152 — `$key = online_orders_normalize_tracking($tn);`
- `includes/shopee_spx_packing_list.php`:174 — `$key = online_orders_normalize_tracking((string) ($row['tracking'] ?? ''));`

## online_orders_ocr_confusables_for_match {#online_orders_ocr_confusables_for_match}

**Callers (1):**

- `includes/online_orders_generator.php`:322 — `return online_orders_ocr_confusables_for_match($t);`

## online_orders_existing_tracking_map {#online_orders_existing_tracking_map}

**Tables:** `online_order_parcels`, `csr_online_order_lines`, `csr_online_batches`

**Callers (1):**

- `includes/online_orders_generator.php`:406 — `$map = online_orders_existing_tracking_map($pdo);`

## online_orders_validate_tracking_duplicates {#online_orders_validate_tracking_duplicates}

**Callers (1):**

- `includes/online_orders_generator.php`:459 — `$conflicts = online_orders_validate_tracking_duplicates($pdo, $pipeline, $lines);`

## online_orders_assert_no_duplicate_tracking {#online_orders_assert_no_duplicate_tracking}

**Callers (3):**

- `includes/online_orders_generator.php`:476 — `online_orders_assert_no_duplicate_tracking($pdo, $pipeline, $lines);`
- `includes/online_orders_generator.php`:540 — `online_orders_assert_no_duplicate_tracking($pdo, 'partner', $lines);`
- `includes/online_orders_generator.php`:696 — `online_orders_assert_no_duplicate_tracking($pdo, 'shopee_spx', $linePayload);`

## online_orders_lines_append {#online_orders_lines_append}

**Tables:** `csr_online_order_lines`

**Callers (3):**

- `api/v1/online_orders.php`:168 — `$added = online_orders_lines_append($pdo, $batchId, $runId, $pipeline, $lines);`
- `includes/online_orders_generator.php`:545 — `$added = online_orders_lines_append($pdo, $batchId, $runId, 'partner', $lines);`
- `includes/online_orders_generator.php`:700 — `online_orders_lines_append($pdo, $batchId, $runId, 'shopee_spx', $linePayload);`

## online_orders_run_partner_stamp {#online_orders_run_partner_stamp}

**Tables:** `csr_online_batch_runs`

**Callers (1):**

- `api/v1/online_orders.php`:198 — `$result = online_orders_run_partner_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelTokens);`

## online_orders_run_vip_stamp {#online_orders_run_vip_stamp}

**Tables:** `csr_online_batch_runs`

**Callers (1):**

- `api/v1/online_orders.php`:223 — `$result = online_orders_run_vip_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_build_ipyx_excel_from_rows {#online_orders_build_ipyx_excel_from_rows}

**Callers (1):**

- `includes/online_orders_generator.php`:699 — `$excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'Shopee SPX — Online Orders');`

## online_orders_run_shopee_resolve {#online_orders_run_shopee_resolve}

**Callers (1):**

- `api/v1/online_orders.php`:246 — `$result = online_orders_run_shopee_resolve($pdo, (int) $user['id'], $batchId, $rows);`

## online_orders_run_shopee_stamp {#online_orders_run_shopee_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:271 — `$result = online_orders_run_shopee_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_merge_master_workbook {#online_orders_merge_master_workbook}

**Tables:** `csr_online_order_lines`

**Callers (1):**

- `includes/online_orders_generator.php`:861 — `$metaJson = online_orders_merge_master_workbook($pdo, $batchId);`

## online_orders_register_master_download {#online_orders_register_master_download}

**Callers (1):**

- `includes/online_orders_generator.php`:864 — `$token = online_orders_register_master_download($metaJson);`

## online_orders_serve_download {#online_orders_serve_download}

**Callers (1):**

- `api/v1/online_orders.php`:13 — `online_orders_serve_download(trim((string) ($_GET['token'] ?? '')));`

## online_orders_batch_finalize {#online_orders_batch_finalize}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:138 — `$result = online_orders_batch_finalize($pdo, (int) $user['id'], $batchId);`

