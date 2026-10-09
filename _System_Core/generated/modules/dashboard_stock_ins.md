---
title: dashboard_stock_ins
source: includes/dashboard_stock_ins.php
generated: 2026-10-09T05:57:32+02:00
type: module
function_count: 10
---


# dashboard_stock_ins

> Source: `includes/dashboard_stock_ins.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/honey_bottle_barcodes.php`

## dashboard_stock_ins_family_keys {#dashboard_stock_ins_family_keys}

**Callers (1):**

- `includes/dashboard_stock_ins.php`:208 — `if (!in_array($family, dashboard_stock_ins_family_keys(), true)) {`

## dashboard_stock_ins_family_options {#dashboard_stock_ins_family_options}

**Callers (1):**

- `includes/dashboard_stock_ins.php`:397 — `'families' => dashboard_stock_ins_family_options(),`

## dashboard_stock_ins_product_series_defs {#dashboard_stock_ins_product_series_defs}

**Callers (2):**

- `includes/dashboard_stock_ins.php`:244 — `$series = dashboard_stock_ins_product_series_defs();`
- `includes/dashboard_stock_ins.php`:360 — `foreach (dashboard_stock_ins_product_series_defs() as $opt) {`

## dashboard_stock_ins_variant_series_defs {#dashboard_stock_ins_variant_series_defs}

**Callers (2):**

- `includes/dashboard_stock_ins.php`:100 — `foreach (dashboard_stock_ins_variant_series_defs() as $v) {`
- `includes/dashboard_stock_ins.php`:281 — `$series = dashboard_stock_ins_variant_series_defs();`

## dashboard_stock_ins_size_series_defs {#dashboard_stock_ins_size_series_defs}

**Callers (2):**

- `includes/dashboard_stock_ins.php`:113 — `foreach (dashboard_stock_ins_size_series_defs() as $s) {`
- `includes/dashboard_stock_ins.php`:311 — `$series = dashboard_stock_ins_size_series_defs();`

## dashboard_stock_ins_variant_options {#dashboard_stock_ins_variant_options}

**Callers (1):**

- `includes/dashboard_stock_ins.php`:398 — `'variants' => dashboard_stock_ins_variant_options(),`

## dashboard_stock_ins_size_options {#dashboard_stock_ins_size_options}

**Callers (1):**

- `includes/dashboard_stock_ins.php`:399 — `'sizes' => dashboard_stock_ins_size_options(),`

## dashboard_stock_ins_resolve_days {#dashboard_stock_ins_resolve_days}

**Callers (1):**

- `includes/dashboard_stock_ins.php`:226 — `$range = dashboard_stock_ins_resolve_days($days);`

## dashboard_stock_ins_fill_points {#dashboard_stock_ins_fill_points}

**Callers (1):**

- `includes/dashboard_stock_ins.php`:385 — `$filled = dashboard_stock_ins_fill_points($range, $byDateKey, $series);`

## dashboard_stock_ins_daily_series {#dashboard_stock_ins_daily_series}

**Tables:** `sku_barcode_stock_events`, `product_sku_barcodes`

**Callers (1):**

- `api/v1/dashboard.php`:398 — `$series = dashboard_stock_ins_daily_series(ims_pdo(), $family, $variant, $size, $days);`

