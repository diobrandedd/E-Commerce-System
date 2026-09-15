---
title: partner_sold_reconcile
source: includes/partner_sold_reconcile.php
generated: 2026-09-15T10:58:41+02:00
type: module
function_count: 16
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

- `includes/partner_sold_reconcile.php`:183 — `$stashDir = partner_sold_storage_root() . '/stash';`
- `includes/partner_sold_reconcile.php`:368 — `$outDir = partner_sold_storage_root() . '/outputs';`

## partner_sold_workbook_script {#partner_sold_workbook_script}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:77 — `$script = partner_sold_workbook_script();`

## partner_sold_user_can_access {#partner_sold_user_can_access}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:39 — `if (!partner_sold_user_can_access($user)) {`

## partner_sold_require_access {#partner_sold_require_access}

**Callers (3):**

- `api/v1/partner_sold_reconcile.php`:26 — `partner_sold_require_access();`
- `api/v1/partner_sold_reconcile.php`:39 — `partner_sold_require_access();`
- `includes/partner_sold_reconcile.php`:424 — `partner_sold_require_access();`

## partner_sold_normalize_order_no {#partner_sold_normalize_order_no}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:268 — `$norm = partner_sold_normalize_order_no((string) ($row['order_no'] ?? ''));`
- `includes/partner_sold_reconcile.php`:330 — `$orderNo = partner_sold_normalize_order_no(isset($row['order_no']) ? (string) $row['order_no'] : null);`

## partner_sold_python_command {#partner_sold_python_command}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:119 — `$built = partner_sold_python_command(...$args);`

## partner_sold_write_temp_json {#partner_sold_write_temp_json}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:381 — `$matchesPath = partner_sold_write_temp_json($matchesPayload);`

## partner_sold_run_python {#partner_sold_run_python}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:300 — `$scan = partner_sold_run_python('scan', $srcPath);`
- `includes/partner_sold_reconcile.php`:383 — `partner_sold_run_python('highlight', $srcPath, $outPath, $matchesPath);`

## partner_sold_stash_prune {#partner_sold_stash_prune}

**Callers (2):**

- `includes/partner_sold_reconcile.php`:177 — `partner_sold_stash_prune();`
- `includes/partner_sold_reconcile.php`:209 — `partner_sold_stash_prune();`

## partner_sold_stash_upload {#partner_sold_stash_upload}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:28 — `$token = partner_sold_stash_upload();`

## partner_sold_resolve_stash_path {#partner_sold_resolve_stash_path}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:299 — `$srcPath = partner_sold_resolve_stash_path($token);`

## partner_sold_stash_original_name {#partner_sold_stash_original_name}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:388 — `$original = partner_sold_stash_original_name($token);`

## partner_sold_shipped_order_no_set {#partner_sold_shipped_order_no_set}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/partner_sold_reconcile.php`:306 — `$shipped = partner_sold_shipped_order_no_set(`

## partner_sold_reconcile {#partner_sold_reconcile}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:42 — `$result = partner_sold_reconcile(ims_pdo(), $input);`

## partner_sold_register_download {#partner_sold_register_download}

**Callers (1):**

- `includes/partner_sold_reconcile.php`:394 — `$downloadToken = partner_sold_register_download($outPath, $downloadFilename);`

## partner_sold_serve_download {#partner_sold_serve_download}

**Callers (1):**

- `api/v1/partner_sold_reconcile.php`:56 — `partner_sold_serve_download($token);`

