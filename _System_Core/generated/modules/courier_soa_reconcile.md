---
title: courier_soa_reconcile
source: includes/courier_soa_reconcile.php
generated: 2026-09-12T11:55:58+02:00
type: module
function_count: 20
---


# courier_soa_reconcile

> Source: `includes/courier_soa_reconcile.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`
- `includes/courier_detect.php`
- `includes/waybill_stamper.php`
- `includes/rts_storage.php`

## courier_soa_storage_root {#courier_soa_storage_root}

**Callers (2):**

- `includes/courier_soa_reconcile.php`:196 — `$stashDir = courier_soa_storage_root() . '/stash';`
- `includes/courier_soa_reconcile.php`:393 — `$outDir = courier_soa_storage_root() . '/outputs';`

## courier_soa_workbook_script {#courier_soa_workbook_script}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:66 — `$script = courier_soa_workbook_script();`

## courier_soa_user_can_access {#courier_soa_user_can_access}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:40 — `if (!courier_soa_user_can_access($user)) {`

## courier_soa_require_access {#courier_soa_require_access}

**Callers (3):**

- `api/v1/courier_soa_reconcile.php`:26 — `courier_soa_require_access();`
- `api/v1/courier_soa_reconcile.php`:39 — `courier_soa_require_access();`
- `includes/courier_soa_reconcile.php`:576 — `courier_soa_require_access();`

## courier_soa_normalize_tracking {#courier_soa_normalize_tracking}

**Callers (2):**

- `includes/courier_soa_reconcile.php`:358 — `$norm = courier_soa_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/courier_soa_reconcile.php`:456 — `$tracking = courier_soa_normalize_tracking(isset($row['tracking']) ? (string) $row['tracking'] : null);`

## courier_soa_python_command {#courier_soa_python_command}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:108 — `$built = courier_soa_python_command(...$args);`

## courier_soa_write_temp_json {#courier_soa_write_temp_json}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:490 — `$matchesPath = courier_soa_write_temp_json($matchesPayload);`

## courier_soa_run_python {#courier_soa_run_python}

**Callers (2):**

- `includes/courier_soa_reconcile.php`:411 — `$scan = courier_soa_run_python('scan', $srcPath);`
- `includes/courier_soa_reconcile.php`:492 — `courier_soa_run_python('highlight', $srcPath, $outPath, $matchesPath);`

## courier_soa_stash_unlink_meta {#courier_soa_stash_unlink_meta}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:180 — `courier_soa_stash_unlink_meta($meta);`

## courier_soa_stash_prune {#courier_soa_stash_prune}

**Callers (2):**

- `includes/courier_soa_reconcile.php`:220 — `courier_soa_stash_prune();`
- `includes/courier_soa_reconcile.php`:243 — `courier_soa_stash_prune();`

## courier_soa_stash_files_from_upload {#courier_soa_stash_files_from_upload}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:221 — `$files = courier_soa_stash_files_from_upload();`

## courier_soa_stash_upload {#courier_soa_stash_upload}

**Callers (1):**

- `api/v1/courier_soa_reconcile.php`:28 — `$token = courier_soa_stash_upload();`

## courier_soa_resolve_stash_files {#courier_soa_resolve_stash_files}

**Callers (3):**

- `includes/courier_soa_reconcile.php`:294 — `$files = courier_soa_resolve_stash_files($token);`
- `includes/courier_soa_reconcile.php`:301 — `$files = courier_soa_resolve_stash_files($token);`
- `includes/courier_soa_reconcile.php`:392 — `$stashFiles = courier_soa_resolve_stash_files($token);`

## courier_soa_resolve_stash_path {#courier_soa_resolve_stash_path}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## courier_soa_stash_original_name {#courier_soa_stash_original_name}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## courier_soa_format_label {#courier_soa_format_label}

**Callers (2):**

- `includes/courier_soa_reconcile.php`:506 — `'courier_label' => courier_soa_format_label($format),`
- `includes/courier_soa_reconcile.php`:527 — `$labels[] = courier_soa_format_label($key);`

## courier_soa_shipped_tracking_map {#courier_soa_shipped_tracking_map}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/courier_soa_reconcile.php`:438 — `$ours = courier_soa_shipped_tracking_map($pdo, $courierKeys, $dateFrom, $dateTo);`

## courier_soa_reconcile {#courier_soa_reconcile}

**Callers (1):**

- `api/v1/courier_soa_reconcile.php`:42 — `$result = courier_soa_reconcile(ims_pdo(), $input);`

## courier_soa_register_download {#courier_soa_register_download}

**Callers (1):**

- `includes/courier_soa_reconcile.php`:502 — `$downloadToken = courier_soa_register_download($outPath, $downloadFilename);`

## courier_soa_serve_download {#courier_soa_serve_download}

**Callers (1):**

- `api/v1/courier_soa_reconcile.php`:56 — `courier_soa_serve_download($token);`

