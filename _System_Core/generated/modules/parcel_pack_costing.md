---
title: parcel_pack_costing
source: includes/parcel_pack_costing.php
generated: 2026-06-18T09:06:37+02:00
type: module
function_count: 37
---


# parcel_pack_costing

> Source: `includes/parcel_pack_costing.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_parcel_item_parser.php`
- `includes/dept_products.php`
- `includes/dept_inventory.php`
- `includes/material_inventory.php`

## parcel_pack_tables_ready {#parcel_pack_tables_ready}

**Tables:** `parcel_pack_tiers`

**Callers (13):**

- `api/v1/costing.php`:143 — `$includeParcel = !empty($_GET['parcel']) && parcel_pack_tables_ready($pdo);`
- `api/v1/costing.php`:172 — `'parcel_ready' => parcel_pack_tables_ready($pdo),`
- `api/v1/costing.php`:490 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:506 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:564 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:586 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/fulfillment_pack_tier_seed.php`:428 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:220 — `if ($lines === [] || !parcel_pack_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1108 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:307 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:374 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:592 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:1027 — `if (!parcel_pack_tables_ready($pdo)) {`

## parcel_pack_order_shape_from_parsed {#parcel_pack_order_shape_from_parsed}

**Callers (2):**

- `includes/parcel_pack_costing.php`:378 — `$shape = parcel_pack_order_shape_from_parsed($parsed);`
- `includes/parcel_pack_costing.php`:636 — `$shape = parcel_pack_order_shape_from_parsed($parsed);`

## parcel_pack_shape_float_eq {#parcel_pack_shape_float_eq}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_shape_has_honey {#parcel_pack_shape_has_honey}

**Callers (6):**

- `includes/parcel_pack_costing.php`:141 — `return parcel_pack_shape_has_honey($shape)`
- `includes/parcel_pack_costing.php`:153 — `&& !parcel_pack_shape_has_honey($shape)`
- `includes/parcel_pack_costing.php`:163 — `&& !parcel_pack_shape_has_honey($shape)`
- `includes/parcel_pack_costing.php`:174 — `&& !parcel_pack_shape_has_honey($shape)`
- `includes/parcel_pack_costing.php`:182 — `if (parcel_pack_shape_has_honey($shape)) {`
- `includes/parcel_pack_costing.php`:223 — `&& !parcel_pack_shape_has_honey($shape)`

## parcel_pack_shape_is_bulk_only {#parcel_pack_shape_is_bulk_only}

**Callers (2):**

- `includes/parcel_pack_costing.php`:207 — `return parcel_pack_shape_is_bulk_only($shape, '350');`
- `includes/parcel_pack_costing.php`:215 — `return parcel_pack_shape_is_bulk_only($shape, '250');`

## parcel_pack_shape_is_solo_honey {#parcel_pack_shape_is_solo_honey}

**Callers (1):**

- `includes/parcel_pack_costing.php`:233 — `if (!empty($rule['solo_honey']) && !parcel_pack_shape_is_solo_honey($shape)) {`

## parcel_pack_shape_is_solo_dept_pt {#parcel_pack_shape_is_solo_dept_pt}

**Callers (1):**

- `includes/parcel_pack_costing.php`:236 — `if (!empty($rule['solo_dept_pt']) && !parcel_pack_shape_is_solo_dept_pt($shape)) {`

## parcel_pack_shape_is_solo_dept_brc {#parcel_pack_shape_is_solo_dept_brc}

**Callers (1):**

- `includes/parcel_pack_costing.php`:239 — `if (!empty($rule['solo_dept_brc']) && !parcel_pack_shape_is_solo_dept_brc($shape)) {`

## parcel_pack_shape_is_solo_jar {#parcel_pack_shape_is_solo_jar}

**Callers (1):**

- `includes/parcel_pack_costing.php`:242 — `if (!empty($rule['solo_jar']) && !parcel_pack_shape_is_solo_jar($shape)) {`

## parcel_pack_shape_is_combo {#parcel_pack_shape_is_combo}

**Callers (1):**

- `includes/parcel_pack_costing.php`:229 — `if (!parcel_pack_shape_is_combo($shape)) {`

## parcel_pack_tier_matches {#parcel_pack_tier_matches}

**Callers (1):**

- `includes/parcel_pack_costing.php`:322 — `if (parcel_pack_tier_matches($rule, $shape)) {`

## parcel_pack_resolve_tier_key_from_shape {#parcel_pack_resolve_tier_key_from_shape}

**Tables:** `parcel_pack_tiers`

**Callers (2):**

- `includes/parcel_pack_costing.php`:364 — `$key = parcel_pack_resolve_tier_key_from_shape($pdo, $shape);`
- `includes/parcel_pack_costing.php`:637 — `$tierKey = parcel_pack_resolve_tier_key_from_shape($pdo, $shape);`

## parcel_pack_pick_honey_tier_key {#parcel_pack_pick_honey_tier_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_pick_dept_tier_key {#parcel_pack_pick_dept_tier_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_pick_cacao_tier_key {#parcel_pack_pick_cacao_tier_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_resolve_tier_keys_from_shape {#parcel_pack_resolve_tier_keys_from_shape}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_resolve_tier_keys_from_shape_db {#parcel_pack_resolve_tier_keys_from_shape_db}

**Callers (1):**

- `includes/parcel_pack_costing.php`:380 — `return parcel_pack_resolve_tier_keys_from_shape_db($pdo, $shape);`

## parcel_pack_resolve_tier_keys_from_items_raw {#parcel_pack_resolve_tier_keys_from_items_raw}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_fetch_tier_by_key {#parcel_pack_fetch_tier_by_key}

**Tables:** `parcel_pack_tiers`

**Callers (4):**

- `includes/fulfillment_packing.php`:250 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, true);`
- `includes/parcel_pack_costing.php`:529 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, $activeOnly);`
- `includes/parcel_pack_costing.php`:556 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, false);`
- `includes/parcel_pack_costing.php`:651 — `$tierRow = parcel_pack_fetch_tier_by_key($pdo, $tierKey, false);`

## parcel_pack_fetch_tier_lines {#parcel_pack_fetch_tier_lines}

**Tables:** `parcel_pack_tier_lines`, `stocks`

**Callers (3):**

- `includes/fulfillment_packing.php`:254 — `$tierLines = parcel_pack_fetch_tier_lines($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:511 — `$lines = parcel_pack_fetch_tier_lines($pdo, $tierId);`
- `includes/parcel_pack_costing.php`:770 — `$lines = parcel_pack_fetch_tier_lines($pdo, $tierId);`

## parcel_pack_line_unit_cost {#parcel_pack_line_unit_cost}

**Callers (1):**

- `includes/parcel_pack_costing.php`:424 — `'unit_cost' => parcel_pack_line_unit_cost($pdo, $row),`

## parcel_pack_sum_line_costs {#parcel_pack_sum_line_costs}

**Callers (1):**

- `includes/parcel_pack_costing.php`:512 — `$sum = parcel_pack_sum_line_costs($lines);`

## parcel_pack_tier_cost_estimate {#parcel_pack_tier_cost_estimate}

**Tables:** `parcel_pack_tiers`

**Callers (5):**

- `api/v1/costing.php`:546 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`
- `api/v1/costing.php`:574 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`
- `includes/parcel_pack_costing.php`:534 — `return parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:571 — `$est = parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:604 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`

## parcel_pack_tier_cost_estimate_by_key {#parcel_pack_tier_cost_estimate_by_key}

**Callers (2):**

- `includes/parcel_pack_costing.php`:646 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, $tierKey);`
- `includes/parcel_pack_costing.php`:1056 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, $tierKey);`

## parcel_pack_tier_is_placeholder {#parcel_pack_tier_is_placeholder}

**Callers (1):**

- `includes/parcel_pack_costing.php`:560 — `if (parcel_pack_tier_is_placeholder($tier)) {`

## parcel_pack_preview_tier_by_key {#parcel_pack_preview_tier_by_key}

**Callers (1):**

- `api/v1/costing.php`:594 — `api_ok(parcel_pack_preview_tier_by_key($pdo, $tierKey));`

## parcel_pack_list_tiers {#parcel_pack_list_tiers}

**Tables:** `parcel_pack_tiers`

**Callers (1):**

- `api/v1/costing.php`:494 — `'tiers' => parcel_pack_list_tiers($pdo, true),`

## parcel_pack_preview_from_items_raw {#parcel_pack_preview_from_items_raw}

**Callers (2):**

- `api/v1/costing.php`:599 — `api_ok(parcel_pack_preview_from_items_raw($pdo, $itemsRaw));`
- `includes/parcel_pack_costing.php`:695 — `$preview = parcel_pack_preview_from_items_raw($pdo, $itemsRaw);`

## parcel_pack_fulfillment_lines_for_items_raw {#parcel_pack_fulfillment_lines_for_items_raw}

**Callers (1):**

- `includes/material_inventory.php`:1119 — `$lines = parcel_pack_fulfillment_lines_for_items_raw($pdo, $itemsRaw);`

## parcel_pack_save_tier_line {#parcel_pack_save_tier_line}

**Tables:** `parcel_pack_tier_lines`

**Callers (1):**

- `api/v1/costing.php`:534 — `$result = parcel_pack_save_tier_line(`

## parcel_pack_delete_tier_line {#parcel_pack_delete_tier_line}

**Tables:** `parcel_pack_tier_lines`

**Callers (1):**

- `api/v1/costing.php`:573 — `parcel_pack_delete_tier_line($pdo, $lineId, $tierId);`

## parcel_pack_formula_current_unit_sql {#parcel_pack_formula_current_unit_sql}

**Callers (2):**

- `includes/parcel_pack_costing.php`:837 — `$unitSql = parcel_pack_formula_current_unit_sql();`
- `includes/parcel_pack_costing.php`:952 — `COALESCE(SUM(f.qty_per_unit * (" . parcel_pack_formula_current_unit_sql() . ")), 0) AS unit_cogs`

## parcel_pack_representative_shape_from_match_rule {#parcel_pack_representative_shape_from_match_rule}

**Callers (1):**

- `includes/parcel_pack_costing.php`:880 — `$shape = parcel_pack_representative_shape_from_match_rule($matchRule);`

## parcel_pack_fetch_product_unit_cogs {#parcel_pack_fetch_product_unit_cogs}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (2):**

- `includes/parcel_pack_costing.php`:888 — `$product = parcel_pack_fetch_product_unit_cogs($pdo, $key, null);`
- `includes/parcel_pack_costing.php`:916 — `$product = parcel_pack_fetch_product_unit_cogs($pdo, null, $size, $honeyVariant);`

## parcel_pack_tier_product_cogs_lines {#parcel_pack_tier_product_cogs_lines}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (1):**

- `includes/parcel_pack_costing.php`:1001 — `$lines = parcel_pack_tier_product_cogs_lines($pdo, $matchRule);`

## parcel_pack_tier_landed_summary {#parcel_pack_tier_landed_summary}

**Callers (3):**

- `includes/parcel_pack_costing.php`:576 — `$landed = parcel_pack_tier_landed_summary($pdo, is_array($rule) ? $rule : [], $est);`
- `includes/parcel_pack_costing.php`:621 — `'product_cogs' => parcel_pack_tier_landed_summary(`
- `includes/parcel_pack_costing.php`:656 — `$productCogs = parcel_pack_tier_landed_summary($pdo, is_array($rule) ? $rule : [], $est);`

## parcel_pack_reference_addon_for_product {#parcel_pack_reference_addon_for_product}

**Callers (1):**

- `api/v1/costing.php`:146 — `$addon = parcel_pack_reference_addon_for_product(`

