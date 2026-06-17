---
title: parcel_pack_costing
source: includes/parcel_pack_costing.php
generated: 2026-06-17T10:42:40+02:00
type: module
function_count: 32
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

- `api/v1/costing.php`:138 — `$includeParcel = !empty($_GET['parcel']) && parcel_pack_tables_ready($pdo);`
- `api/v1/costing.php`:167 — `'parcel_ready' => parcel_pack_tables_ready($pdo),`
- `api/v1/costing.php`:471 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:487 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:545 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:567 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/fulfillment_pack_tier_seed.php`:431 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:89 — `if ($lines === [] || !parcel_pack_tables_ready($pdo)) {`
- `includes/material_inventory.php`:900 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:307 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:374 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:575 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:756 — `if (!parcel_pack_tables_ready($pdo)) {`

## parcel_pack_order_shape_from_parsed {#parcel_pack_order_shape_from_parsed}

**Callers (2):**

- `includes/parcel_pack_costing.php`:378 — `$shape = parcel_pack_order_shape_from_parsed($parsed);`
- `includes/parcel_pack_costing.php`:614 — `$shape = parcel_pack_order_shape_from_parsed($parsed);`

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
- `includes/parcel_pack_costing.php`:615 — `$tierKey = parcel_pack_resolve_tier_key_from_shape($pdo, $shape);`

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

**Callers (3):**

- `includes/fulfillment_packing.php`:119 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, true);`
- `includes/parcel_pack_costing.php`:518 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, $activeOnly);`
- `includes/parcel_pack_costing.php`:545 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, false);`

## parcel_pack_fetch_tier_lines {#parcel_pack_fetch_tier_lines}

**Tables:** `parcel_pack_tier_lines`, `stocks`

**Callers (3):**

- `includes/fulfillment_packing.php`:123 — `$tierLines = parcel_pack_fetch_tier_lines($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:500 — `$lines = parcel_pack_fetch_tier_lines($pdo, $tierId);`
- `includes/parcel_pack_costing.php`:739 — `$lines = parcel_pack_fetch_tier_lines($pdo, $tierId);`

## parcel_pack_line_unit_cost {#parcel_pack_line_unit_cost}

**Callers (1):**

- `includes/parcel_pack_costing.php`:424 — `'unit_cost' => parcel_pack_line_unit_cost($pdo, $row),`

## parcel_pack_sum_line_costs {#parcel_pack_sum_line_costs}

**Callers (1):**

- `includes/parcel_pack_costing.php`:501 — `$sum = parcel_pack_sum_line_costs($lines);`

## parcel_pack_tier_cost_estimate {#parcel_pack_tier_cost_estimate}

**Tables:** `parcel_pack_tiers`

**Callers (5):**

- `api/v1/costing.php`:527 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`
- `api/v1/costing.php`:555 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`
- `includes/parcel_pack_costing.php`:523 — `return parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:560 — `$est = parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:587 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`

## parcel_pack_tier_cost_estimate_by_key {#parcel_pack_tier_cost_estimate_by_key}

**Callers (4):**

- `includes/parcel_pack_costing.php`:623 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, $tierKey);`
- `includes/parcel_pack_costing.php`:760 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, 'dept_pt_1_2');`
- `includes/parcel_pack_costing.php`:770 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, 'cacao_1_jar');`
- `includes/parcel_pack_costing.php`:781 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, 'honey_1x_1L');`

## parcel_pack_tier_is_placeholder {#parcel_pack_tier_is_placeholder}

**Callers (1):**

- `includes/parcel_pack_costing.php`:549 — `if (parcel_pack_tier_is_placeholder($tier)) {`

## parcel_pack_preview_tier_by_key {#parcel_pack_preview_tier_by_key}

**Callers (1):**

- `api/v1/costing.php`:575 — `api_ok(parcel_pack_preview_tier_by_key($pdo, $tierKey));`

## parcel_pack_list_tiers {#parcel_pack_list_tiers}

**Tables:** `parcel_pack_tiers`

**Callers (1):**

- `api/v1/costing.php`:475 — `'tiers' => parcel_pack_list_tiers($pdo, true),`

## parcel_pack_preview_from_items_raw {#parcel_pack_preview_from_items_raw}

**Callers (2):**

- `api/v1/costing.php`:580 — `api_ok(parcel_pack_preview_from_items_raw($pdo, $itemsRaw));`
- `includes/parcel_pack_costing.php`:664 — `$preview = parcel_pack_preview_from_items_raw($pdo, $itemsRaw);`

## parcel_pack_fulfillment_lines_for_items_raw {#parcel_pack_fulfillment_lines_for_items_raw}

**Callers (1):**

- `includes/material_inventory.php`:911 — `$lines = parcel_pack_fulfillment_lines_for_items_raw($pdo, $itemsRaw);`

## parcel_pack_save_tier_line {#parcel_pack_save_tier_line}

**Tables:** `parcel_pack_tier_lines`

**Callers (1):**

- `api/v1/costing.php`:515 — `$result = parcel_pack_save_tier_line(`

## parcel_pack_delete_tier_line {#parcel_pack_delete_tier_line}

**Tables:** `parcel_pack_tier_lines`

**Callers (1):**

- `api/v1/costing.php`:554 — `parcel_pack_delete_tier_line($pdo, $lineId, $tierId);`

## parcel_pack_reference_addon_for_product {#parcel_pack_reference_addon_for_product}

**Callers (1):**

- `api/v1/costing.php`:141 — `$addon = parcel_pack_reference_addon_for_product(`

