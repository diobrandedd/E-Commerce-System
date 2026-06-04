---
title: online_orders_generator
source: includes/online_orders_generator.php
generated: 2026-06-04T11:09:09+02:00
type: module
function_count: 22
---


# online_orders_generator

> Source: `includes/online_orders_generator.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/waybill_stamper.php`
- `includes/online_order_parcels.php`

## online_orders_coord {#online_orders_coord}

**Callers (19):**

- `includes/online_orders_generator.php`:115 — `$v = $sheet->getCell(online_orders_coord($c, $r))->getValue();`
- `includes/online_orders_generator.php`:154 — `$v = $sheet->getCell(online_orders_coord($col, $r))->getValue();`
- `includes/online_orders_generator.php`:466 — `$sheet->setCellValue(online_orders_coord($col, $headerRow), $text);`
- `includes/online_orders_generator.php`:467 — `$sheet->getStyle(online_orders_coord($col, $headerRow))->getFont()->setBold(true);`
- `includes/online_orders_generator.php`:473 — `$sheet->setCellValue(online_orders_coord(2, $r), '');`
- `includes/online_orders_generator.php`:474 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) ($row['name'] ?? ''));`
- `includes/online_orders_generator.php`:475 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:477 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:478 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) ($row['courier'] ?? ''));`
- `includes/online_orders_generator.php`:479 — `$sheet->getCell(online_orders_coord(8, $r))`
- `includes/online_orders_generator.php`:605 — `$coord = online_orders_coord($col, $headerRow);`
- `includes/online_orders_generator.php`:619 — `$sheet->setCellValue(online_orders_coord(1, $r), $seq++);`
- `includes/online_orders_generator.php`:620 — `$sheet->setCellValue(online_orders_coord(2, $r), (string) ($row['order_no'] ?? ''));`
- `includes/online_orders_generator.php`:621 — `$sheet->setCellValue(online_orders_coord(3, $r), (string) $row['recipient_name']);`
- `includes/online_orders_generator.php`:622 — `$sheet->getCell(online_orders_coord(4, $r))`
- `includes/online_orders_generator.php`:624 — `$sheet->setCellValue(online_orders_coord(5, $r), '');`
- `includes/online_orders_generator.php`:625 — `$sheet->setCellValue(online_orders_coord(6, $r), '');`
- `includes/online_orders_generator.php`:626 — `$sheet->setCellValue(online_orders_coord(7, $r), (string) $row['courier']);`
- `includes/online_orders_generator.php`:627 — `$sheet->getCell(online_orders_coord(8, $r))`

## online_orders_storage_root {#online_orders_storage_root}

**Callers (2):**

- `includes/online_orders_generator.php`:484 — `$dir = online_orders_storage_root() . '/work';`
- `includes/online_orders_generator.php`:637 — `$path = online_orders_storage_root() . '/outputs/master_' . bin2hex(random_bytes(8)) . '.xlsx';`

## online_orders_user_can_access {#online_orders_user_can_access}

**Callers (2):**

- `api/v1/online_orders.php`:24 — `if (!online_orders_user_can_access($user)) {`
- `includes/online_orders_generator.php`:46 — `if (!online_orders_user_can_access($user)) {`

## online_orders_require_access {#online_orders_require_access}

**Callers (1):**

- `includes/online_orders_generator.php`:666 — `online_orders_require_access();`

## online_orders_ensure_schema {#online_orders_ensure_schema}

**Tables:** `csr_online_batches`

**Callers (3):**

- `includes/online_orders_generator.php`:191 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:214 — `online_orders_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:267 — `online_orders_ensure_schema($pdo);`

## online_orders_parse_ipyx_excel_paths {#online_orders_parse_ipyx_excel_paths}

**Callers (2):**

- `includes/online_orders_generator.php`:366 — `$parsed = online_orders_parse_ipyx_excel_paths($excels);`
- `includes/online_orders_generator.php`:417 — `$parsed = online_orders_parse_ipyx_excel_paths($excels);`

## online_orders_batch_start {#online_orders_batch_start}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:60 — `$batch = online_orders_batch_start($pdo, (int) $user['id']);`

## online_orders_batch_get {#online_orders_batch_get}

**Tables:** `csr_online_batches`, `csr_online_batch_runs`, `csr_online_order_lines`

**Callers (3):**

- `api/v1/online_orders.php`:76 — `$batch = online_orders_batch_get($pdo, (int) $user['id'], $batchId > 0 ? $batchId : null);`
- `includes/online_orders_generator.php`:206 — `return online_orders_batch_get($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:711 — `$batch = online_orders_batch_get($pdo, $userId, $batchId);`

## online_orders_batch_cancel {#online_orders_batch_cancel}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:97 — `online_orders_batch_cancel($pdo, (int) $user['id'], $batchId);`

## online_orders_assert_active_batch {#online_orders_assert_active_batch}

**Tables:** `csr_online_batches`

**Callers (6):**

- `api/v1/online_orders.php`:145 — `online_orders_assert_active_batch($pdo, (int) $user['id'], $batchId);`
- `includes/online_orders_generator.php`:357 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:408 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:509 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:557 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`
- `includes/online_orders_generator.php`:695 — `online_orders_assert_active_batch($pdo, $userId, $batchId);`

## online_orders_run_start {#online_orders_run_start}

**Tables:** `csr_online_batch_runs`

**Callers (4):**

- `api/v1/online_orders.php`:146 — `$runId = online_orders_run_start($pdo, $batchId, $pipeline);`
- `includes/online_orders_generator.php`:363 — `$runId = online_orders_run_start($pdo, $batchId, 'partner');`
- `includes/online_orders_generator.php`:414 — `$runId = online_orders_run_start($pdo, $batchId, 'vip');`
- `includes/online_orders_generator.php`:536 — `$runId = online_orders_run_start($pdo, $batchId, 'shopee_spx');`

## online_orders_run_complete {#online_orders_run_complete}

**Tables:** `csr_online_batch_runs`

**Callers (4):**

- `api/v1/online_orders.php`:148 — `online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'source' => 'manual_append']);`
- `includes/online_orders_generator.php`:378 — `online_orders_run_complete($pdo, $runId, [`
- `includes/online_orders_generator.php`:429 — `online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'stamp_matched' => $stamp['matched']]);`
- `includes/online_orders_generator.php`:539 — `online_orders_run_complete($pdo, $runId, ['excel_token' => $excelToken, 'row_count' => count($normalized)]);`

## online_orders_lines_append {#online_orders_lines_append}

**Tables:** `csr_online_order_lines`

**Callers (4):**

- `api/v1/online_orders.php`:147 — `$added = online_orders_lines_append($pdo, $batchId, $runId, $pipeline, $lines);`
- `includes/online_orders_generator.php`:377 — `$added = online_orders_lines_append($pdo, $batchId, $runId, 'partner', $lines);`
- `includes/online_orders_generator.php`:428 — `$added = online_orders_lines_append($pdo, $batchId, $runId, 'vip', $lines);`
- `includes/online_orders_generator.php`:538 — `online_orders_lines_append($pdo, $batchId, $runId, 'shopee_spx', $linePayload);`

## online_orders_run_partner_stamp {#online_orders_run_partner_stamp}

**Tables:** `csr_online_batch_runs`

**Callers (1):**

- `api/v1/online_orders.php`:173 — `$result = online_orders_run_partner_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelTokens);`

## online_orders_run_vip_stamp {#online_orders_run_vip_stamp}

**Tables:** `csr_online_batch_runs`

**Callers (1):**

- `api/v1/online_orders.php`:200 — `$result = online_orders_run_vip_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_build_ipyx_excel_from_rows {#online_orders_build_ipyx_excel_from_rows}

**Callers (1):**

- `includes/online_orders_generator.php`:537 — `$excelToken = online_orders_build_ipyx_excel_from_rows($normalized, 'Shopee SPX — Online Orders');`

## online_orders_run_shopee_resolve {#online_orders_run_shopee_resolve}

**Callers (1):**

- `api/v1/online_orders.php`:223 — `$result = online_orders_run_shopee_resolve($pdo, (int) $user['id'], $batchId, $rows);`

## online_orders_run_shopee_stamp {#online_orders_run_shopee_stamp}

**Callers (1):**

- `api/v1/online_orders.php`:248 — `$result = online_orders_run_shopee_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);`

## online_orders_merge_master_workbook {#online_orders_merge_master_workbook}

**Tables:** `csr_online_order_lines`

**Callers (1):**

- `includes/online_orders_generator.php`:699 — `$metaJson = online_orders_merge_master_workbook($pdo, $batchId);`

## online_orders_register_master_download {#online_orders_register_master_download}

**Callers (1):**

- `includes/online_orders_generator.php`:702 — `$token = online_orders_register_master_download($metaJson);`

## online_orders_serve_download {#online_orders_serve_download}

**Callers (1):**

- `api/v1/online_orders.php`:12 — `online_orders_serve_download(trim((string) ($_GET['token'] ?? '')));`

## online_orders_batch_finalize {#online_orders_batch_finalize}

**Tables:** `csr_online_batches`

**Callers (1):**

- `api/v1/online_orders.php`:118 — `$result = online_orders_batch_finalize($pdo, (int) $user['id'], $batchId);`

