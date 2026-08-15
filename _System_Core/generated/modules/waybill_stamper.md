---
title: waybill_stamper
source: includes/waybill_stamper.php
generated: 2026-08-15T04:21:02+02:00
type: module
function_count: 19
---


# waybill_stamper

> Source: `includes/waybill_stamper.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/rts_storage.php`

## waybill_stamper_storage_root {#waybill_stamper_storage_root}

**Callers (3):**

- `includes/waybill_stamper.php`:202 — `$uploadDir = $uploadDir ?? waybill_stamper_storage_root() . '/uploads';`
- `includes/waybill_stamper.php`:271 — `$stashDir = waybill_stamper_storage_root() . '/stash';`
- `includes/waybill_stamper.php`:373 — `$root = waybill_stamper_storage_root();`

## waybill_stamper_processor_script {#waybill_stamper_processor_script}

**Callers (1):**

- `includes/waybill_stamper.php`:368 — `$script = waybill_stamper_processor_script();`

## waybill_stamper_require_access {#waybill_stamper_require_access}

**Callers (4):**

- `includes/order_remarks.php`:28 — `return waybill_stamper_require_access();`
- `includes/pdf_to_excel.php`:28 — `return waybill_stamper_require_access();`
- `includes/waybill_stamper.php`:489 — `waybill_stamper_require_access();`
- `includes/waybill_stamper.php`:540 — `waybill_stamper_require_access();`

## waybill_stamper_user_is_csr_dept {#waybill_stamper_user_is_csr_dept}

**Tables:** `departments`

**Callers (1):**

- `includes/waybill_stamper.php`:66 — `return waybill_stamper_user_is_csr_dept($user);`

## waybill_stamper_user_can_access {#waybill_stamper_user_can_access}

**Callers (7):**

- `api/v1/pdf_to_excel.php`:33 — `if (!waybill_stamper_user_can_access($user)) {`
- `api/v1/waybill.php`:40 — `if (!waybill_stamper_user_can_access($user)) {`
- `includes/online_orders_generator.php`:78 — `return waybill_stamper_user_can_access($user);`
- `includes/order_remarks.php`:34 — `return waybill_stamper_user_can_access($user);`
- `includes/waybill_stamper.php`:32 — `if (!waybill_stamper_user_can_access($user)) {`
- `pdf-to-excel/auth_gate.php`:11 — `if ($user === null || !waybill_stamper_user_can_access($user)) {`
- `waybill-stamper/auth_gate.php`:11 — `if ($user === null || !waybill_stamper_user_can_access($user)) {`

## waybill_stamper_parse_ini_size {#waybill_stamper_parse_ini_size}

**Callers (1):**

- `includes/waybill_stamper.php`:105 — `$postMax = waybill_stamper_parse_ini_size((string) ini_get('post_max_size'));`

## waybill_stamper_upload_preflight {#waybill_stamper_upload_preflight}

**Callers (7):**

- `api/v1/pdf_to_excel.php`:62 — `waybill_stamper_upload_preflight();`
- `api/v1/waybill.php`:72 — `waybill_stamper_upload_preflight();`
- `api/v1/waybill.php`:104 — `waybill_stamper_upload_preflight();`
- `includes/courier_soa_reconcile.php`:191 — `waybill_stamper_upload_preflight();`
- `includes/historical_orders_import.php`:139 — `waybill_stamper_upload_preflight();`
- `includes/order_remarks.php`:365 — `waybill_stamper_upload_preflight();`
- `includes/partner_sold_reconcile.php`:178 — `waybill_stamper_upload_preflight();`

## waybill_stamper_files_field {#waybill_stamper_files_field}

**Callers (2):**

- `api/v1/waybill.php`:107 — `$pdfField = waybill_stamper_files_field('pdfs');`
- `api/v1/waybill.php`:112 — `$excelField = waybill_stamper_files_field('excels');`

## waybill_stamper_utf8 {#waybill_stamper_utf8}

**Callers (8):**

- `includes/courier_soa_reconcile.php`:136 — `throw new RuntimeException('Failed to parse Python output: ' . substr(waybill_stamper_utf8($out), 0, 400));`
- `includes/historical_orders_import.php`:89 — `throw new RuntimeException('Failed to parse workbook output: ' . substr(waybill_stamper_utf8($out), 0, 400));`
- `includes/order_remarks.php`:328 — `throw new RuntimeException('Failed to parse Python output: ' . substr(waybill_stamper_utf8($out), 0, 400));`
- `includes/partner_sold_reconcile.php`:147 — `throw new RuntimeException('Failed to parse Python output: ' . substr(waybill_stamper_utf8($out), 0, 400));`
- `includes/pdf_to_excel.php`:223 — `$hint = waybill_stamper_utf8(trim($output));`
- `includes/pdf_to_excel.php`:303 — `$logExcerpt = waybill_stamper_utf8($output);`
- `includes/waybill_stamper.php`:422 — `$hint = waybill_stamper_utf8($hint);`
- `includes/waybill_stamper.php`:443 — `$logExcerpt = waybill_stamper_utf8($log);`

## waybill_stamper_resolve_python {#waybill_stamper_resolve_python}

**Callers (6):**

- `includes/courier_soa_reconcile.php`:65 — `$python = waybill_stamper_resolve_python();`
- `includes/historical_orders_import.php`:37 — `$python = waybill_stamper_resolve_python();`
- `includes/order_remarks.php`:224 — `$python = waybill_stamper_resolve_python();`
- `includes/partner_sold_reconcile.php`:76 — `$python = waybill_stamper_resolve_python();`
- `includes/pdf_to_excel.php`:198 — `$python = waybill_stamper_resolve_python();`
- `includes/waybill_stamper.php`:385 — `$python = waybill_stamper_resolve_python();`

## waybill_stamper_collect_uploads {#waybill_stamper_collect_uploads}

**Callers (8):**

- `api/v1/waybill.php`:109 — `$pdfs = waybill_stamper_collect_uploads($pdfField, ['pdf'], 'pdf_');`
- `api/v1/waybill.php`:114 — `$excels = waybill_stamper_collect_uploads($excelField, ['xlsx', 'xls'], 'xls_');`
- `includes/courier_soa_reconcile.php`:197 — `$saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'csoa_', $stashDir);`
- `includes/historical_orders_import.php`:147 — `$saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'hist_', $stashDir);`
- `includes/order_remarks.php`:373 — `$saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'xlsx_', $stashDir);`
- `includes/partner_sold_reconcile.php`:184 — `$saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'ps_', $stashDir);`
- `includes/pdf_to_excel.php`:60 — `$saved = waybill_stamper_collect_uploads($field, ['pdf'], 'pdf_', $stashDir);`
- `includes/waybill_stamper.php`:272 — `$saved = waybill_stamper_collect_uploads($field, $allowed, $prefix, $stashDir);`

## waybill_stamper_stash_prune {#waybill_stamper_stash_prune}

**Callers (3):**

- `includes/waybill_stamper.php`:261 — `waybill_stamper_stash_prune();`
- `includes/waybill_stamper.php`:298 — `waybill_stamper_stash_prune();`
- `includes/waybill_stamper.php`:542 — `waybill_stamper_stash_prune();`

## waybill_stamper_stash_upload {#waybill_stamper_stash_upload}

**Callers (1):**

- `api/v1/waybill.php`:73 — `$token = waybill_stamper_stash_upload($kind);`

## waybill_stamper_resolve_stash_tokens {#waybill_stamper_resolve_stash_tokens}

**Callers (7):**

- `api/v1/waybill.php`:101 — `$pdfs = waybill_stamper_resolve_stash_tokens($pdfTokenList, 'pdf');`
- `api/v1/waybill.php`:102 — `$excels = waybill_stamper_resolve_stash_tokens($excelTokenList, 'excel');`
- `includes/online_orders_generator.php`:619 — `$paths = waybill_stamper_resolve_stash_tokens($tokens, 'excel');`
- `includes/online_orders_generator.php`:976 — `$pdfs = waybill_stamper_resolve_stash_tokens($pdfTokens, 'pdf');`
- `includes/online_orders_generator.php`:977 — `$excels = waybill_stamper_resolve_stash_tokens($excelTokenList, 'excel');`
- `includes/shopee_spx_packing_list.php`:207 — `$excels = waybill_stamper_resolve_stash_tokens([$excelToken], 'excel');`
- `includes/shopee_spx_packing_list.php`:211 — `$pdfs = waybill_stamper_resolve_stash_tokens(array_values($pdfTokens), 'pdf');`

## waybill_stamper_release_stash_tokens {#waybill_stamper_release_stash_tokens}

**Callers (4):**

- `api/v1/waybill.php`:119 — `waybill_stamper_release_stash_tokens($stashTokensToRelease);`
- `api/v1/waybill.php`:123 — `waybill_stamper_release_stash_tokens($stashTokensToRelease);`
- `includes/online_orders_generator.php`:1014 — `waybill_stamper_release_stash_tokens($stashRelease);`
- `includes/online_orders_generator.php`:1018 — `waybill_stamper_release_stash_tokens($stashRelease);`

## waybill_stamper_process_uploads {#waybill_stamper_process_uploads}

**Callers (2):**

- `api/v1/waybill.php`:118 — `$result = waybill_stamper_process_uploads($pdfs, $excels);`
- `includes/online_orders_generator.php`:999 — `$stamp = waybill_stamper_process_uploads($pdfs, $excels);`

## waybill_stamper_register_download {#waybill_stamper_register_download}

**Callers (1):**

- `includes/waybill_stamper.php`:441 — `$token = waybill_stamper_register_download($outputFile, $downloadFilename);`

## waybill_stamper_serve_download {#waybill_stamper_serve_download}

**Callers (1):**

- `api/v1/waybill.php`:12 — `waybill_stamper_serve_download(trim((string) ($_GET['token'] ?? '')));`

## waybill_stamper_serve_stash_download {#waybill_stamper_serve_stash_download}

**Callers (1):**

- `api/v1/waybill.php`:20 — `waybill_stamper_serve_stash_download(trim((string) ($_GET['token'] ?? '')));`

