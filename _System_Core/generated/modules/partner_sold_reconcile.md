---
title: partner_sold_reconcile
source: includes/partner_sold_reconcile.php
generated: 2026-10-09T06:32:40+02:00
type: module
function_count: 25
---


# partner_sold_reconcile

> Source: `includes/partner_sold_reconcile.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`
- `includes/online_orders_generator.php`
- `includes/waybill_stamper.php`

## partner_sold_storage_root {#partner_sold_storage_root}

**Callers (3):**

- `includes/partner_sold_reconcile.php`:226 — `$stashDir = partner_sold_storage_root() . '/stash';`
- `includes/partner_sold_reconcile.php`:562 — `$outDir = partner_sold_storage_root() . '/outputs';`
- `includes/partner_sold_reconcile.php`:688 — `$outDir = partner_sold_storage_root() . '/outputs';`

## partner_sold_workbook_script {#partner_sold_workbook_script}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:115 — `$script = partner_sold_workbook_script();`

## partner_sold_user_can_access {#partner_sold_user_can_access}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:40 — `if (!partner_sold_user_can_access($user)) {`

## partner_sold_require_access {#partner_sold_require_access}

**Callers (3):**

- `api/v1/partner_sold_reconcile.php`:26 — `partner_sold_require_access();`
- `api/v1/partner_sold_reconcile.php`:39 — `partner_sold_require_access();`
- `includes/partner_sold_reconcile.php`:775 — `partner_sold_require_access();`

## partner_sold_normalize_order_no {#partner_sold_normalize_order_no}

**Callers (4):**

- `includes/partner_sold_reconcile.php`:301 — `$norm = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);`
- `includes/partner_sold_reconcile.php`:389 — `$orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);`
- `includes/partner_sold_reconcile.php`:514 — `$orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);`
- `includes/partner_sold_reconcile.php`:645 — `$orderNo = partner_sold_normalize_order_no(`

## partner_sold_normalize_tracking {#partner_sold_normalize_tracking}

**Callers (4):**

- `includes/partner_sold_reconcile.php`:305 — `$tn = partner_sold_normalize_tracking(isset($row['tracking_number']) ? (string) $row['tracking_number'] : null);`
- `includes/partner_sold_reconcile.php`:390 — `$tracking = partner_sold_normalize_tracking(`
- `includes/partner_sold_reconcile.php`:515 — `$tracking = partner_sold_normalize_tracking(`
- `includes/partner_sold_reconcile.php`:648 — `$tracking = partner_sold_normalize_tracking(`

## partner_sold_order_no_lookup_keys {#partner_sold_order_no_lookup_keys}

**Callers (3):**

- `includes/partner_sold_reconcile.php`:302 — `foreach (partner_sold_order_no_lookup_keys($norm) as $key) {`
- `includes/partner_sold_reconcile.php`:403 — `foreach (partner_sold_order_no_lookup_keys($orderNo) as $key) {`
- `includes/partner_sold_reconcile.php`:441 — `foreach (partner_sold_order_no_lookup_keys($orderNo) as $key) {`

## partner_sold_python_command {#partner_sold_python_command}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:162 — `$built = partner_sold_python_command(...$args);`

## partner_sold_write_temp_json {#partner_sold_write_temp_json}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:575 — `$matchesPath = partner_sold_write_temp_json($matchesPayload);`
- `includes/partner_sold_reconcile.php`:700 — `$rowsPath = partner_sold_write_temp_json($payload);`

## partner_sold_run_python {#partner_sold_run_python}

**Callers (3):**

- `includes/partner_sold_reconcile.php`:577 — `partner_sold_run_python('highlight', $srcPath, $outPath, $matchesPath);`
- `includes/partner_sold_reconcile.php`:702 — `partner_sold_run_python('export_ours', $rowsPath, $outPath);`
- `includes/partner_sold_reconcile.php`:748 — `$scan = partner_sold_run_python('scan', $srcPath);`

## partner_sold_stash_prune {#partner_sold_stash_prune}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:220 — `partner_sold_stash_prune();`
- `includes/partner_sold_reconcile.php`:252 — `partner_sold_stash_prune();`

## partner_sold_stash_upload {#partner_sold_stash_upload}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:28 — `$token = partner_sold_stash_upload();`

## partner_sold_resolve_stash_path {#partner_sold_resolve_stash_path}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:747 — `$srcPath = partner_sold_resolve_stash_path($token);`

## partner_sold_stash_original_name {#partner_sold_stash_original_name}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:582 — `$original = partner_sold_stash_original_name($token);`

## partner_sold_existing_parcel_indexes {#partner_sold_existing_parcel_indexes}

**Callers (3):**

- `includes/partner_sold_reconcile.php`:423 — `* @deprecated Use partner_sold_existing_parcel_indexes(); kept for callers/tests expecting the old name.`
- `includes/partner_sold_reconcile.php`:432 — `return partner_sold_existing_parcel_indexes($pdo, $partnerSeller, $dateFrom, $dateTo)['order_nos'];`
- `includes/partner_sold_reconcile.php`:491 — `$indexes = partner_sold_existing_parcel_indexes($pdo, $partnerSeller, $dateFrom, $dateTo);`

## partner_sold_list_our_parcels {#partner_sold_list_our_parcels}

**Tables:** `online_order_parcels`

**Callers (2):**

- `includes/partner_sold_reconcile.php`:297 — `$parcels = partner_sold_list_our_parcels($pdo, $partnerSeller, $dateFrom, $dateTo);`
- `includes/partner_sold_reconcile.php`:629 — `$parcels = partner_sold_list_our_parcels($pdo, $partnerSeller, $dateFrom, $dateTo);`

## partner_sold_indexes_from_excel_rows {#partner_sold_indexes_from_excel_rows}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:625 — `$excelIdx = partner_sold_indexes_from_excel_rows($excelRows);`

## partner_sold_shipped_order_no_set {#partner_sold_shipped_order_no_set}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## partner_sold_row_exists {#partner_sold_row_exists}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:532 — `if (partner_sold_row_exists($orderNos, $trackings, $orderNo, $tracking)) {`
- `includes/partner_sold_reconcile.php`:655 — `$exists = partner_sold_row_exists($theirOrderNos, $theirTrackings, $orderNo, $tracking);`

## partner_sold_normalize_direction {#partner_sold_normalize_direction}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:742 — `$direction = partner_sold_normalize_direction((string) ($input['direction'] ?? 'ours_in_theirs'));`

## partner_sold_reconcile_theirs_in_ours {#partner_sold_reconcile_theirs_in_ours}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:751 — `return partner_sold_reconcile_theirs_in_ours($pdo, $partnerSeller, $srcPath, $token, $scan, $from, $to);`

## partner_sold_reconcile_ours_in_theirs {#partner_sold_reconcile_ours_in_theirs}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:754 — `return partner_sold_reconcile_ours_in_theirs($pdo, $partnerSeller, $token, $scan, $from, $to);`

## partner_sold_reconcile {#partner_sold_reconcile}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:42 — `$result = partner_sold_reconcile(ims_pdo(), $input);`

## partner_sold_register_download {#partner_sold_register_download}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:588 — `$downloadToken = partner_sold_register_download($outPath, $downloadFilename);`
- `includes/partner_sold_reconcile.php`:708 — `$downloadToken = partner_sold_register_download($outPath, $downloadFilename);`

## partner_sold_serve_download {#partner_sold_serve_download}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:56 — `partner_sold_serve_download($token);`

