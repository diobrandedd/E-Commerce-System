---
title: rts_inventory_sync
source: includes/rts_inventory_sync.php
generated: 2026-08-15T07:46:08+02:00
type: module
function_count: 22
---


# rts_inventory_sync

> Source: `includes/rts_inventory_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/rts_schema.php`
- `includes/honey_inventory.php`
- `includes/honey_inventory.php`
- `includes/honey_inventory.php`
- `includes/inventory_edit_log.php`
- `includes/inventory_edit_log.php`
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

- `includes/rts_case_handlers.php`:575 — `rts_case_replace_line_items($pdo, $caseId, $fields['lines']);`

## rts_inventory_sync_status {#rts_inventory_sync_status}

**Tables:** `rts_sync_state`

**Callers (3):**

- `api/v1/rts.php`:146 — `$sync = rts_inventory_sync_status($pdo);`
- `api/v1/rts.php`:284 — `api_ok(rts_inventory_sync_status(ims_pdo()));`
- `api/v1/rts_sync.php`:39 — `api_ok(rts_inventory_sync_status(ims_pdo()));`

## rts_inventory_pool_filter_sql {#rts_inventory_pool_filter_sql}

**Callers (9):**

- `api/v1/rts.php`:57 — `return rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:260 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:290 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:346 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:436 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:610 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:708 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:937 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:952 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`

## rts_inventory_list_honey_aggregated {#rts_inventory_list_honey_aggregated}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:141 — `$honey = rts_inventory_list_honey_aggregated($pdo, $pool);`

## rts_inventory_list_other_aggregated {#rts_inventory_list_other_aggregated}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:142 — `$other = rts_inventory_list_other_aggregated($pdo, $pool);`

## rts_inventory_deduct_sell_honey_variant {#rts_inventory_deduct_sell_honey_variant}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (4):**

- `api/v1/rts.php`:233 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_bottle_barcodes.php`:1282 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_pos.php`:406 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_pos.php`:625 — `rts_inventory_deduct_sell_honey_variant(`

## rts_inventory_deduct_sell_other_label {#rts_inventory_deduct_sell_other_label}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (3):**

- `api/v1/rts.php`:243 — `rts_inventory_deduct_sell_other_label(`
- `includes/dept_pos.php`:189 — `rts_inventory_deduct_sell_other_label(`
- `includes/dept_pos.php`:333 — `rts_inventory_deduct_sell_other_label(`

## rts_inventory_deduct_sell {#rts_inventory_deduct_sell}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (1):**

- `api/v1/rts.php`:253 — `rts_inventory_deduct_sell(`

## rts_inventory_set_honey_variant_totals {#rts_inventory_set_honey_variant_totals}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:296 — `$result = rts_inventory_set_honey_variant_totals(`

## rts_inventory_set_other_label_total {#rts_inventory_set_other_label_total}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:322 — `$result = rts_inventory_set_other_label_total(`

## rts_inventory_seed_honey_row {#rts_inventory_seed_honey_row}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `includes/rts_inventory_sync.php`:631 — `rts_inventory_seed_honey_row($pdo, $variant, $pool);`

## rts_inventory_seed_other_row {#rts_inventory_seed_other_row}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `includes/rts_inventory_sync.php`:719 — `rts_inventory_seed_other_row($pdo, $productLabel, $pool);`

## rts_pool_use_log_insert {#rts_pool_use_log_insert}

**Tables:** `rts_pool_use_log`

**Callers (3):**

- `includes/rts_inventory_sync.php`:469 — `rts_pool_use_log_insert($pdo, [`
- `includes/rts_inventory_sync.php`:564 — `rts_pool_use_log_insert($pdo, [`
- `includes/rts_inventory_sync.php`:918 — `rts_pool_use_log_insert($pdo, [`

## rts_pool_use_log_write_honey {#rts_pool_use_log_write_honey}

**Callers (2):**

- `includes/rts_inventory_sync.php`:401 — `rts_pool_use_log_write_honey($pdo, $variant, $sizeQtys, $userId, $opts);`
- `includes/rts_inventory_sync.php`:547 — `rts_pool_use_log_write_honey(`

## rts_inventory_available_honey {#rts_inventory_available_honey}

**Tables:** `rts_inventory_rows`

**Callers (2):**

- `includes/honey_pos.php`:341 — `? rts_inventory_available_honey($pdo, $variant, $size, 'sellable')`
- `includes/honey_pos.php`:564 — `? rts_inventory_available_honey($pdo, $variant, $size, 'sellable')`

## rts_inventory_available_other {#rts_inventory_available_other}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `includes/dept_pos.php`:88 — `$onHand = rts_inventory_available_other($pdo, $rtsLabel, 'sellable');`

## rts_other_label_for_dept_key {#rts_other_label_for_dept_key}

**Callers (2):**

- `includes/dept_pos.php`:84 — `$rtsLabel = rts_other_label_for_dept_key($productKey);`
- `includes/dept_pos.php`:116 — `'rts_label' => $stockSource === 'rts' ? rts_other_label_for_dept_key($productKey) : null,`

## rts_pool_use_log_list {#rts_pool_use_log_list}

**Tables:** `rts_pool_use_log`

**Callers (1):**

- `api/v1/rts.php`:277 — `api_ok(['rows' => rts_pool_use_log_list(ims_pdo(), $limit)]);`

