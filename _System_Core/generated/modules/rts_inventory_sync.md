---
title: rts_inventory_sync
source: includes/rts_inventory_sync.php
generated: 2026-08-26T04:07:23+02:00
type: module
function_count: 25
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
- `includes/honey_inventory.php`
- `includes/inventory_edit_log.php`
- `includes/inventory_edit_log.php`
- `includes/honey_inventory.php`
- `includes/honey_inventory.php`

## rts_inv_product_label {#rts_inv_product_label}

**Callers (1):**

- `includes/rts_inventory_sync.php`:101 — `$label = rts_inv_product_label($productType, $li['combo_product'] ?? null);`

## rts_inv_is_honey_line {#rts_inv_is_honey_line}

**Callers (1):**

- `includes/rts_inventory_sync.php`:99 — `$isHoney = rts_inv_is_honey_line($productType);`

## rts_sellable_from_cases_enabled {#rts_sellable_from_cases_enabled}

**Tables:** `rts_sync_state`

**Callers (7):**

- `api/v1/rts.php`:261 — `if ($pool === 'sellable' && !rts_sellable_from_cases_enabled(ims_pdo())) {`
- `includes/dept_pos.php`:112 — `if (!rts_sellable_from_cases_enabled($pdo)) {`
- `includes/honey_pos.php`:342 — `if (!rts_sellable_from_cases_enabled($pdo)) {`
- `includes/honey_pos.php`:573 — `if (!rts_sellable_from_cases_enabled($pdo)) {`
- `includes/rts_case_handlers.php`:513 — `if ($pool === 'rts_sellable' && !rts_sellable_from_cases_enabled($pdo)) {`
- `includes/rts_inventory_sync.php`:52 — `$sellableFromCases = rts_sellable_from_cases_enabled($pdo);`
- `includes/rts_inventory_sync.php`:267 — `'sellable_from_cases' => rts_sellable_from_cases_enabled($pdo),`

## rts_inventory_sync_run {#rts_inventory_sync_run}

**Tables:** `rts_sync_state`, `rts_line_items`, `rts_cases`, `rts_inventory_rows`

**Callers (2):**

- `api/v1/rts_cases.php`:78 — `rts_inventory_sync_run($pdo);`
- `api/v1/rts_sync.php`:28 — `$result = rts_inventory_sync_run(ims_pdo());`

## rts_inventory_row_is_untouched {#rts_inventory_row_is_untouched}

**Callers (1):**

- `includes/rts_inventory_sync.php`:223 — `if (!rts_inventory_row_is_untouched($ir)) {`

## rts_case_replace_line_items {#rts_case_replace_line_items}

**Tables:** `rts_inventory_rows`, `honey_sales_log`, `rts_damaged_use_events`, `rts_line_items`

**Callers (1):**

- `includes/rts_case_handlers.php`:596 — `rts_case_replace_line_items($pdo, $caseId, $fields['lines']);`

## rts_inventory_sync_status {#rts_inventory_sync_status}

**Tables:** `rts_sync_state`

**Callers (3):**

- `api/v1/rts.php`:146 — `$sync = rts_inventory_sync_status($pdo);`
- `api/v1/rts.php`:321 — `api_ok(rts_inventory_sync_status(ims_pdo()));`
- `api/v1/rts_sync.php`:39 — `api_ok(rts_inventory_sync_status(ims_pdo()));`

## rts_inventory_credit_sellable_honey {#rts_inventory_credit_sellable_honey}

**Tables:** `rts_inventory_rows`

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:1017 — `rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);`
- `includes/honey_bottle_barcodes.php`:1037 — `rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);`
- `includes/pack_cancel_restock.php`:384 — `rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);`

## rts_inventory_credit_sellable_other {#rts_inventory_credit_sellable_other}

**Tables:** `rts_inventory_rows`

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:1159 — `rts_inventory_credit_sellable_other($pdo, $label, 1.0);`
- `includes/dept_pouch_barcodes.php`:1169 — `rts_inventory_credit_sellable_other($pdo, $label, 1.0);`
- `includes/pack_cancel_restock.php`:434 — `rts_inventory_credit_sellable_other($pdo, dept_pouch_product_label($productKey), 1.0);`

## rts_inventory_pool_filter_sql {#rts_inventory_pool_filter_sql}

**Callers (11):**

- `api/v1/rts.php`:57 — `return rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:292 — `$poolSql = rts_inventory_pool_filter_sql($pdo, 'sellable');`
- `includes/rts_inventory_sync.php`:317 — `$poolSql = rts_inventory_pool_filter_sql($pdo, 'sellable');`
- `includes/rts_inventory_sync.php`:345 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:375 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:431 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:521 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:695 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:793 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:1022 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`
- `includes/rts_inventory_sync.php`:1037 — `$poolSql = rts_inventory_pool_filter_sql($pdo, $pool);`

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

**Callers (5):**

- `api/v1/rts.php`:270 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_bottle_barcodes.php`:1608 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_bottle_barcodes.php`:1842 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_pos.php`:414 — `rts_inventory_deduct_sell_honey_variant(`
- `includes/honey_pos.php`:641 — `rts_inventory_deduct_sell_honey_variant(`

## rts_inventory_deduct_sell_other_label {#rts_inventory_deduct_sell_other_label}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (5):**

- `api/v1/rts.php`:280 — `rts_inventory_deduct_sell_other_label(`
- `includes/dept_pos.php`:246 — `rts_inventory_deduct_sell_other_label(`
- `includes/dept_pos.php`:395 — `rts_inventory_deduct_sell_other_label(`
- `includes/dept_pouch_barcodes.php`:1011 — `rts_inventory_deduct_sell_other_label(`
- `includes/honey_bottle_barcodes.php`:1654 — `rts_inventory_deduct_sell_other_label(`

## rts_inventory_deduct_sell {#rts_inventory_deduct_sell}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (1):**

- `api/v1/rts.php`:290 — `rts_inventory_deduct_sell(`

## rts_inventory_set_honey_variant_totals {#rts_inventory_set_honey_variant_totals}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:333 — `$result = rts_inventory_set_honey_variant_totals(`

## rts_inventory_set_other_label_total {#rts_inventory_set_other_label_total}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `api/v1/rts.php`:359 — `$result = rts_inventory_set_other_label_total(`

## rts_inventory_seed_honey_row {#rts_inventory_seed_honey_row}

**Tables:** `rts_inventory_rows`

**Callers (2):**

- `includes/rts_inventory_sync.php`:302 — `$rowId = rts_inventory_seed_honey_row($pdo, $variant, 'sellable');`
- `includes/rts_inventory_sync.php`:716 — `rts_inventory_seed_honey_row($pdo, $variant, $pool);`

## rts_inventory_seed_other_row {#rts_inventory_seed_other_row}

**Tables:** `rts_inventory_rows`

**Callers (2):**

- `includes/rts_inventory_sync.php`:326 — `$rowId = rts_inventory_seed_other_row($pdo, $productLabel, 'sellable');`
- `includes/rts_inventory_sync.php`:804 — `rts_inventory_seed_other_row($pdo, $productLabel, $pool);`

## rts_pool_use_log_insert {#rts_pool_use_log_insert}

**Tables:** `rts_pool_use_log`

**Callers (3):**

- `includes/rts_inventory_sync.php`:554 — `rts_pool_use_log_insert($pdo, [`
- `includes/rts_inventory_sync.php`:649 — `rts_pool_use_log_insert($pdo, [`
- `includes/rts_inventory_sync.php`:1003 — `rts_pool_use_log_insert($pdo, [`

## rts_pool_use_log_write_honey {#rts_pool_use_log_write_honey}

**Callers (2):**

- `includes/rts_inventory_sync.php`:486 — `rts_pool_use_log_write_honey($pdo, $variant, $sizeQtys, $userId, $opts);`
- `includes/rts_inventory_sync.php`:632 — `rts_pool_use_log_write_honey(`

## rts_inventory_available_honey {#rts_inventory_available_honey}

**Tables:** `rts_inventory_rows`

**Callers (2):**

- `includes/honey_pos.php`:349 — `? rts_inventory_available_honey($pdo, $variant, $size, 'sellable')`
- `includes/honey_pos.php`:580 — `? rts_inventory_available_honey($pdo, $variant, $size, 'sellable')`

## rts_inventory_available_other {#rts_inventory_available_other}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `includes/dept_pos.php`:144 — `$onHand = rts_inventory_available_other($pdo, $rtsLabel, 'sellable');`

## rts_other_label_for_dept_key {#rts_other_label_for_dept_key}

**Callers (2):**

- `includes/dept_pos.php`:140 — `$rtsLabel = rts_other_label_for_dept_key($productKey);`
- `includes/dept_pos.php`:172 — `'rts_label' => $stockSource === 'rts' ? rts_other_label_for_dept_key($productKey) : null,`

## rts_pool_use_log_list {#rts_pool_use_log_list}

**Tables:** `rts_pool_use_log`

**Callers (1):**

- `api/v1/rts.php`:314 — `api_ok(['rows' => rts_pool_use_log_list(ims_pdo(), $limit)]);`

