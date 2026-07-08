---
title: rts_inventory_sync
source: includes/rts_inventory_sync.php
generated: 2026-07-08T05:43:14+02:00
type: module
function_count: 12
---


# rts_inventory_sync

> Source: `includes/rts_inventory_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/rts_schema.php`
- `includes/honey_inventory.php`
- `includes/honey_inventory.php`

## rts_inv_product_label {#rts_inv_product_label}

**Callers (1):**

- `includes/rts_inventory_sync.php`:87 — `$label = rts_inv_product_label($productType, $li['combo_product'] ?? null);`

## rts_inv_is_honey_line {#rts_inv_is_honey_line}

**Callers (1):**

- `includes/rts_inventory_sync.php`:85 — `$isHoney = rts_inv_is_honey_line($productType);`

## rts_inventory_sync_run {#rts_inventory_sync_run}

**Tables:** `rts_sync_state`, `rts_line_items`, `rts_cases`, `rts_inventory_rows`

**Callers (2):**

- `api/v1/rts_cases.php`:73 — `rts_inventory_sync_run($pdo);`
- `api/v1/rts_sync.php`:28 — `$result = rts_inventory_sync_run(ims_pdo());`

## rts_inventory_row_is_untouched {#rts_inventory_row_is_untouched}

**Callers (1):**

- `includes/rts_inventory_sync.php`:204 — `if (!rts_inventory_row_is_untouched($ir)) {`

## rts_case_replace_line_items {#rts_case_replace_line_items}

**Tables:** `rts_inventory_rows`, `honey_sales_log`, `rts_damaged_use_events`, `rts_line_items`

**Callers (1):**

- `includes/rts_case_handlers.php`:566 — `rts_case_replace_line_items($pdo, $caseId, $fields['lines']);`

## rts_inventory_sync_status {#rts_inventory_sync_status}

**Tables:** `rts_sync_state`

**Callers (3):**

- `api/v1/rts.php`:140 — `$sync = rts_inventory_sync_status($pdo);`
- `api/v1/rts.php`:249 — `api_ok(rts_inventory_sync_status(ims_pdo()));`
- `api/v1/rts_sync.php`:39 — `api_ok(rts_inventory_sync_status(ims_pdo()));`

## rts_inventory_pool_filter_sql {#rts_inventory_pool_filter_sql}

**Callers (5):**

- `api/v1/rts.php`:51 — `return rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:260 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:290 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:344 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:424 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`

## rts_inventory_list_honey_aggregated {#rts_inventory_list_honey_aggregated}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:135 — `$honey = rts_inventory_list_honey_aggregated($pdo, $pool);`

## rts_inventory_list_other_aggregated {#rts_inventory_list_other_aggregated}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:136 — `$other = rts_inventory_list_other_aggregated($pdo, $pool);`

## rts_inventory_deduct_sell_honey_variant {#rts_inventory_deduct_sell_honey_variant}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (1):**

- `api/v1/rts.php`:212 — `rts_inventory_deduct_sell_honey_variant(`

## rts_inventory_deduct_sell_other_label {#rts_inventory_deduct_sell_other_label}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (1):**

- `api/v1/rts.php`:221 — `rts_inventory_deduct_sell_other_label(`

## rts_inventory_deduct_sell {#rts_inventory_deduct_sell}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (1):**

- `api/v1/rts.php`:230 — `rts_inventory_deduct_sell(`

