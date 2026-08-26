---
title: pdf_to_excel
source: includes/pdf_to_excel.php
generated: 2026-08-26T04:10:10+02:00
type: module
function_count: 15
---


# pdf_to_excel

> Source: `includes/pdf_to_excel.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/waybill_stamper.php`

## pdf_to_excel_storage_root {#pdf_to_excel_storage_root}

**Callers (2):**

- `includes/pdf_to_excel.php`:59 — `$stashDir = pdf_to_excel_storage_root() . '/stash';`
- `includes/pdf_to_excel.php`:187 — `$root = pdf_to_excel_storage_root();`

## pdf_to_excel_extractor_script {#pdf_to_excel_extractor_script}

**Callers (1):**

- `includes/pdf_to_excel.php`:182 — `$script = pdf_to_excel_extractor_script();`

## pdf_to_excel_require_access {#pdf_to_excel_require_access}

**Callers (1):**

- `includes/pdf_to_excel.php`:404 — `pdf_to_excel_require_access();`

## pdf_to_excel_stash_prune {#pdf_to_excel_stash_prune}

**Callers (2):**

- `includes/pdf_to_excel.php`:52 — `pdf_to_excel_stash_prune();`
- `includes/pdf_to_excel.php`:85 — `pdf_to_excel_stash_prune();`

## pdf_to_excel_stash_upload {#pdf_to_excel_stash_upload}

**Callers (1):**

- `api/v1/pdf_to_excel.php`:63 — `$token = pdf_to_excel_stash_upload();`

## pdf_to_excel_resolve_stash_tokens {#pdf_to_excel_resolve_stash_tokens}

**Callers (1):**

- `api/v1/pdf_to_excel.php`:88 — `$pdfs = pdf_to_excel_resolve_stash_tokens($tokenList);`

## pdf_to_excel_release_stash_tokens {#pdf_to_excel_release_stash_tokens}

**Callers (2):**

- `api/v1/pdf_to_excel.php`:93 — `pdf_to_excel_release_stash_tokens($stashToRelease);`
- `api/v1/pdf_to_excel.php`:96 — `pdf_to_excel_release_stash_tokens($stashToRelease);`

## pdf_to_excel_jobs_prune {#pdf_to_excel_jobs_prune}

**Callers (2):**

- `includes/pdf_to_excel.php`:294 — `pdf_to_excel_jobs_prune();`
- `includes/pdf_to_excel.php`:332 — `pdf_to_excel_jobs_prune();`

## pdf_to_excel_delete_job_files {#pdf_to_excel_delete_job_files}

**Callers (2):**

- `includes/pdf_to_excel.php`:145 — `pdf_to_excel_delete_job_files($job);`
- `includes/pdf_to_excel.php`:493 — `pdf_to_excel_delete_job_files($job);`

## pdf_to_excel_extract {#pdf_to_excel_extract}

**Callers (2):**

- `api/v1/pdf_to_excel.php`:92 — `$result = pdf_to_excel_extract($pdfs);`
- `includes/shopee_spx_packing_list.php`:145 — `$extract = pdf_to_excel_extract($pdfPaths);`

## pdf_to_excel_confirm {#pdf_to_excel_confirm}

**Callers (1):**

- `api/v1/pdf_to_excel.php`:114 — `$result = pdf_to_excel_confirm($jobToken);`

## pdf_to_excel_register_download {#pdf_to_excel_register_download}

**Callers (1):**

- `includes/pdf_to_excel.php`:365 — `'download_token' => pdf_to_excel_register_download($path, $filename, $jobToken),`

## pdf_to_excel_serve_download {#pdf_to_excel_serve_download}

**Callers (1):**

- `api/v1/pdf_to_excel.php`:12 — `pdf_to_excel_serve_download(trim((string) ($_GET['token'] ?? '')));`

## pdf_to_excel_maybe_cleanup_job {#pdf_to_excel_maybe_cleanup_job}

**Callers (1):**

- `includes/pdf_to_excel.php`:457 — `pdf_to_excel_maybe_cleanup_job($jobToken, $path);`

## pdf_to_excel_rmdir {#pdf_to_excel_rmdir}

**Callers (5):**

- `includes/pdf_to_excel.php`:227 — `pdf_to_excel_rmdir($workDir);`
- `includes/pdf_to_excel.php`:237 — `pdf_to_excel_rmdir($workDir);`
- `includes/pdf_to_excel.php`:285 — `pdf_to_excel_rmdir($workDir);`
- `includes/pdf_to_excel.php`:496 — `pdf_to_excel_rmdir($workDir);`
- `includes/pdf_to_excel.php`:516 — `pdf_to_excel_rmdir($path);`

