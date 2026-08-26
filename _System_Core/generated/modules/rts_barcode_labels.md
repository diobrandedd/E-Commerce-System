---
title: rts_barcode_labels
source: includes/rts_barcode_labels.php
generated: 2026-08-26T04:10:14+02:00
type: module
function_count: 11
---


# rts_barcode_labels

> Source: `includes/rts_barcode_labels.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/permissions.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`

## rts_barcode_labels_can {#rts_barcode_labels_can}

**Callers (1):**

- `api/v1/rts_barcode_labels.php`:24 — `if (!rts_barcode_labels_can($user)) {`

## rts_barcode_label_serialize_job {#rts_barcode_label_serialize_job}

**Callers (2):**

- `includes/rts_barcode_labels.php`:146 — `return rts_barcode_label_serialize_job($row, rts_barcode_label_job_lines($pdo, $jobId));`
- `includes/rts_barcode_labels.php`:186 — `$rows[] = rts_barcode_label_serialize_job($row, []);`

## rts_barcode_label_job_scan_stats {#rts_barcode_label_job_scan_stats}

**Tables:** `rts_barcode_label_job_lines`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/rts_barcode_labels.php`:142 — `$stats = rts_barcode_label_job_scan_stats($pdo, $jobId);`

## rts_barcode_label_serialize_line {#rts_barcode_label_serialize_line}

**Callers (1):**

- `includes/rts_barcode_labels.php`:118 — `$out[] = rts_barcode_label_serialize_line($row);`

## rts_barcode_label_job_lines {#rts_barcode_label_job_lines}

**Tables:** `rts_barcode_label_job_lines`

**Callers (3):**

- `includes/rts_barcode_labels.php`:146 — `return rts_barcode_label_serialize_job($row, rts_barcode_label_job_lines($pdo, $jobId));`
- `includes/rts_barcode_labels.php`:276 — `'INSERT INTO rts_barcode_label_job_lines (`
- `includes/rts_barcode_labels.php`:301 — `'INSERT INTO rts_barcode_label_job_lines (`

## rts_barcode_label_job_get {#rts_barcode_label_job_get}

**Tables:** `rts_barcode_label_job_lines`, `rts_barcode_label_jobs`, `users`

**Callers (6):**

- `api/v1/rts_barcode_labels.php`:90 — `api_ok(['job' => rts_barcode_label_job_get(ims_pdo(), $id)]);`
- `includes/rts_barcode_labels.php`:328 — `return rts_barcode_label_job_get($pdo, $jobId);`
- `includes/rts_barcode_labels.php`:333 — `$job = rts_barcode_label_job_get($pdo, $jobId);`
- `includes/rts_barcode_labels.php`:346 — `return rts_barcode_label_job_get($pdo, $jobId);`
- `includes/rts_barcode_labels.php`:359 — `$job = rts_barcode_label_job_get($pdo, $jobId);`
- `includes/rts_barcode_labels.php`:425 — `$job = rts_barcode_label_job_get($pdo, $jobId);`

## rts_barcode_label_job_list {#rts_barcode_label_job_list}

**Tables:** `rts_barcode_label_jobs`, `rts_barcode_label_job_lines`, `honey_bottle_barcodes`, `users`

**Callers (1):**

- `api/v1/rts_barcode_labels.php`:100 — `api_ok(rts_barcode_label_job_list(`

## rts_barcode_label_job_create {#rts_barcode_label_job_create}

**Tables:** `rts_barcode_label_jobs`, `rts_barcode_label_job_lines`

**Callers (1):**

- `api/v1/rts_barcode_labels.php`:73 — `api_ok(['job' => rts_barcode_label_job_create(`

## rts_barcode_label_job_mark_printed {#rts_barcode_label_job_mark_printed}

**Tables:** `rts_barcode_label_jobs`

**Callers (1):**

- `api/v1/rts_barcode_labels.php`:126 — `api_ok(['job' => rts_barcode_label_job_mark_printed(ims_pdo(), (int) ($in['id'] ?? 0))]);`

## rts_barcode_label_job_codes {#rts_barcode_label_job_codes}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`

**Callers (1):**

- `api/v1/rts_barcode_labels.php`:113 — `api_ok(rts_barcode_label_job_codes(ims_pdo(), $id));`

## rts_barcode_label_print_sheet_html {#rts_barcode_label_print_sheet_html}

**Callers (1):**

- `api/v1/rts_barcode_labels.php`:141 — `$html = rts_barcode_label_print_sheet_html(ims_pdo(), $id);`

