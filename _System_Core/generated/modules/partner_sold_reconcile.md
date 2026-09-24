---
title: partner_sold_reconcile
source: includes/partner_sold_reconcile.php
generated: 2026-09-24T09:58:29+02:00
type: module
function_count: 20
---


# partner_sold_reconcile

> Source: `includes/partner_sold_reconcile.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`
- `includes/online_orders_generator.php`
- `includes/waybill_stamper.php`

## partner_sold_storage_root {#partner_sold_storage_root}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:220 — `$stashDir = partner_sold_storage_root() . '/stash';`
- `includes/partner_sold_reconcile.php`:473 — `$outDir = partner_sold_storage_root() . '/outputs';`

## partner_sold_workbook_script {#partner_sold_workbook_script}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:114 — `$script = partner_sold_workbook_script();`

## partner_sold_user_can_access {#partner_sold_user_can_access}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:39 — `if (!partner_sold_user_can_access($user)) {`

## partner_sold_require_access {#partner_sold_require_access}

**Callers (3):**

- `api/v1/partner_sold_reconcile.php`:26 — `partner_sold_require_access();`
- `api/v1/partner_sold_reconcile.php`:39 — `partner_sold_require_access();`
- `includes/partner_sold_reconcile.php`:529 — `partner_sold_require_access();`

## partner_sold_normalize_order_no {#partner_sold_normalize_order_no}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:325 — `$norm = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);`
- `includes/partner_sold_reconcile.php`:428 — `$orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);`

## partner_sold_normalize_tracking {#partner_sold_normalize_tracking}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:329 — `$tn = partner_sold_normalize_tracking(isset($row['tracking_number']) ? (string) $row['tracking_number'] : null);`
- `includes/partner_sold_reconcile.php`:429 — `$tracking = partner_sold_normalize_tracking(`

## partner_sold_order_no_lookup_keys {#partner_sold_order_no_lookup_keys}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:326 — `foreach (partner_sold_order_no_lookup_keys($norm) as $key) {`
- `includes/partner_sold_reconcile.php`:358 — `foreach (partner_sold_order_no_lookup_keys($orderNo) as $key) {`

## partner_sold_python_command {#partner_sold_python_command}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:156 — `$built = partner_sold_python_command(...$args);`

## partner_sold_write_temp_json {#partner_sold_write_temp_json}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:486 — `$matchesPath = partner_sold_write_temp_json($matchesPayload);`

## partner_sold_run_python {#partner_sold_run_python}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:395 — `$scan = partner_sold_run_python('scan', $srcPath);`
- `includes/partner_sold_reconcile.php`:488 — `partner_sold_run_python('highlight', $srcPath, $outPath, $matchesPath);`

## partner_sold_stash_prune {#partner_sold_stash_prune}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:214 — `partner_sold_stash_prune();`
- `includes/partner_sold_reconcile.php`:246 — `partner_sold_stash_prune();`

## partner_sold_stash_upload {#partner_sold_stash_upload}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:28 — `$token = partner_sold_stash_upload();`

## partner_sold_resolve_stash_path {#partner_sold_resolve_stash_path}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:394 — `$srcPath = partner_sold_resolve_stash_path($token);`

## partner_sold_stash_original_name {#partner_sold_stash_original_name}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:493 — `$original = partner_sold_stash_original_name($token);`

## partner_sold_existing_parcel_indexes {#partner_sold_existing_parcel_indexes}

**Tables:** `online_order_parcels`

**Callers (3):**

- `includes/partner_sold_reconcile.php`:340 — `* @deprecated Use partner_sold_existing_parcel_indexes(); kept for callers/tests expecting the old name.`
- `includes/partner_sold_reconcile.php`:349 — `return partner_sold_existing_parcel_indexes($pdo, $partnerSeller, $dateFrom, $dateTo)['order_nos'];`
- `includes/partner_sold_reconcile.php`:401 — `$indexes = partner_sold_existing_parcel_indexes(`

## partner_sold_shipped_order_no_set {#partner_sold_shipped_order_no_set}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## partner_sold_row_exists {#partner_sold_row_exists}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:446 — `if (partner_sold_row_exists($orderNos, $trackings, $orderNo, $tracking)) {`

## partner_sold_reconcile {#partner_sold_reconcile}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:42 — `$result = partner_sold_reconcile(ims_pdo(), $input);`

## partner_sold_register_download {#partner_sold_register_download}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:499 — `$downloadToken = partner_sold_register_download($outPath, $downloadFilename);`

## partner_sold_serve_download {#partner_sold_serve_download}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:56 — `partner_sold_serve_download($token);`

