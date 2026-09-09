---
title: barcode_verify
source: includes/barcode_verify.php
generated: 2026-09-08T09:45:23+02:00
type: module
function_count: 3
---


# barcode_verify

> Source: `includes/barcode_verify.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/sku_barcodes.php`

## barcode_verify_lookup {#barcode_verify_lookup}

**Callers (1):**

- `api/v1/barcode_verify.php`:25 — `api_ok(['result' => barcode_verify_lookup(ims_pdo(), $code)]);`

## barcode_verify_lookup_honey {#barcode_verify_lookup_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`, `honey_bottle_units`

**Callers (1):**

- `includes/barcode_verify.php`:53 — `$honey = barcode_verify_lookup_honey($pdo, $code);`

## barcode_verify_lookup_dept {#barcode_verify_lookup_dept}

**Tables:** `users`

**Callers (1):**

- `includes/barcode_verify.php`:58 — `$dept = barcode_verify_lookup_dept($pdo, $code);`

