---
title: waybill_stamper
source: includes/waybill_stamper.php
generated: 2026-06-04T03:07:07+02:00
type: module
function_count: 17
---


# waybill_stamper

> Source: `includes/waybill_stamper.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## waybill_stamper_storage_root {#waybill_stamper_storage_root}

**Callers (3):**

- `includes/waybill_stamper.php`:194 — `$uploadDir = $uploadDir ?? waybill_stamper_storage_root() . '/uploads';`
- `includes/waybill_stamper.php`:263 — `$stashDir = waybill_stamper_storage_root() . '/stash';`
- `includes/waybill_stamper.php`:365 — `$root = waybill_stamper_storage_root();`

## waybill_stamper_processor_script {#waybill_stamper_processor_script}

**Callers (1):**

- `includes/waybill_stamper.php`:360 — `$script = waybill_stamper_processor_script();`

## waybill_stamper_require_access {#waybill_stamper_require_access}

**Callers (3):**

- `includes/order_remarks.php`:31 — `return waybill_stamper_require_access();`
- `includes/pdf_to_excel.php`:32 — `return waybill_stamper_require_access();`
- `includes/waybill_stamper.php`:481 — `waybill_stamper_require_access();`

## waybill_stamper_user_can_access {#waybill_stamper_user_can_access}

**Tables:** `departments`

**Callers (6):**

- `api/v1/pdf_to_excel.php`:33 — `if (!waybill_stamper_user_can_access($user)) {`
- `api/v1/waybill.php`:32 — `if (!waybill_stamper_user_can_access($user)) {`
- `includes/order_remarks.php`:37 — `return waybill_stamper_user_can_access($user);`
- `includes/waybill_stamper.php`:33 — `if (!waybill_stamper_user_can_access($user)) {`
- `pdf-to-excel/auth_gate.php`:11 — `if ($user === null || !waybill_stamper_user_can_access($user)) {`
- `waybill-stamper/auth_gate.php`:11 — `if ($user === null || !waybill_stamper_user_can_access($user)) {`

## waybill_stamper_parse_ini_size {#waybill_stamper_parse_ini_size}

**Callers (1):**

- `includes/waybill_stamper.php`:97 — `$postMax = waybill_stamper_parse_ini_size((string) ini_get('post_max_size'));`

## waybill_stamper_upload_preflight {#waybill_stamper_upload_preflight}

**Callers (4):**

- `api/v1/pdf_to_excel.php`:62 — `waybill_stamper_upload_preflight();`
- `api/v1/waybill.php`:64 — `waybill_stamper_upload_preflight();`
- `api/v1/waybill.php`:96 — `waybill_stamper_upload_preflight();`
- `includes/order_remarks.php`:298 — `waybill_stamper_upload_preflight();`

## waybill_stamper_files_field {#waybill_stamper_files_field}

**Callers (2):**

- `api/v1/waybill.php`:99 — `$pdfField = waybill_stamper_files_field('pdfs');`
- `api/v1/waybill.php`:104 — `$excelField = waybill_stamper_files_field('excels');`

## waybill_stamper_utf8 {#waybill_stamper_utf8}

**Callers (5):**

- `includes/order_remarks.php`:261 — `throw new RuntimeException('Failed to parse Python output: ' . substr(waybill_stamper_utf8($out), 0, 400));`
- `includes/pdf_to_excel.php`:227 — `$hint = waybill_stamper_utf8(trim($output));`
- `includes/pdf_to_excel.php`:307 — `$logExcerpt = waybill_stamper_utf8($output);`
- `includes/waybill_stamper.php`:414 — `$hint = waybill_stamper_utf8($hint);`
- `includes/waybill_stamper.php`:435 — `$logExcerpt = waybill_stamper_utf8($log);`

## waybill_stamper_resolve_python {#waybill_stamper_resolve_python}

**Callers (3):**

- `includes/order_remarks.php`:167 — `$python = waybill_stamper_resolve_python();`
- `includes/pdf_to_excel.php`:202 — `$python = waybill_stamper_resolve_python();`
- `includes/waybill_stamper.php`:377 — `$python = waybill_stamper_resolve_python();`

## waybill_stamper_collect_uploads {#waybill_stamper_collect_uploads}

**Callers (5):**

- `api/v1/waybill.php`:101 — `$pdfs = waybill_stamper_collect_uploads($pdfField, ['pdf'], 'pdf_');`
- `api/v1/waybill.php`:106 — `$excels = waybill_stamper_collect_uploads($excelField, ['xlsx', 'xls'], 'xls_');`
- `includes/order_remarks.php`:306 — `$saved = waybill_stamper_collect_uploads($field, ['xlsx', 'xls'], 'xlsx_', $stashDir);`
- `includes/pdf_to_excel.php`:64 — `$saved = waybill_stamper_collect_uploads($field, ['pdf'], 'pdf_', $stashDir);`
- `includes/waybill_stamper.php`:264 — `$saved = waybill_stamper_collect_uploads($field, $allowed, $prefix, $stashDir);`

## waybill_stamper_stash_prune {#waybill_stamper_stash_prune}

**Callers (2):**

- `includes/waybill_stamper.php`:253 — `waybill_stamper_stash_prune();`
- `includes/waybill_stamper.php`:290 — `waybill_stamper_stash_prune();`

## waybill_stamper_stash_upload {#waybill_stamper_stash_upload}

**Callers (1):**

- `api/v1/waybill.php`:65 — `$token = waybill_stamper_stash_upload($kind);`

## waybill_stamper_resolve_stash_tokens {#waybill_stamper_resolve_stash_tokens}

**Callers (2):**

- `api/v1/waybill.php`:93 — `$pdfs = waybill_stamper_resolve_stash_tokens($pdfTokenList, 'pdf');`
- `api/v1/waybill.php`:94 — `$excels = waybill_stamper_resolve_stash_tokens($excelTokenList, 'excel');`

## waybill_stamper_release_stash_tokens {#waybill_stamper_release_stash_tokens}

**Callers (2):**

- `api/v1/waybill.php`:111 — `waybill_stamper_release_stash_tokens($stashTokensToRelease);`
- `api/v1/waybill.php`:115 — `waybill_stamper_release_stash_tokens($stashTokensToRelease);`

## waybill_stamper_process_uploads {#waybill_stamper_process_uploads}

**Callers (1):**

- `api/v1/waybill.php`:110 — `$result = waybill_stamper_process_uploads($pdfs, $excels);`

## waybill_stamper_register_download {#waybill_stamper_register_download}

**Callers (1):**

- `includes/waybill_stamper.php`:433 — `$token = waybill_stamper_register_download($outputFile, $downloadFilename);`

## waybill_stamper_serve_download {#waybill_stamper_serve_download}

**Callers (1):**

- `api/v1/waybill.php`:12 — `waybill_stamper_serve_download(trim((string) ($_GET['token'] ?? '')));`

