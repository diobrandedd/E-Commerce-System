---
title: parcel_pack_costing
source: includes/parcel_pack_costing.php
generated: 2026-06-16T10:41:00+02:00
type: module
function_count: 21
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

**Callers (9):**

- `api/v1/costing.php`:138 — `$includeParcel = !empty($_GET['parcel']) && parcel_pack_tables_ready($pdo);`
- `api/v1/costing.php`:167 — `'parcel_ready' => parcel_pack_tables_ready($pdo),`
- `api/v1/costing.php`:471 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:487 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:545 — `if (!parcel_pack_tables_ready($pdo)) {`
- `api/v1/costing.php`:567 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:179 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:371 — `if (!parcel_pack_tables_ready($pdo)) {`
- `includes/parcel_pack_costing.php`:521 — `if (!parcel_pack_tables_ready($pdo)) {`

## parcel_pack_order_shape_from_parsed {#parcel_pack_order_shape_from_parsed}

**Callers (2):**

- `includes/parcel_pack_costing.php`:183 — `$shape = parcel_pack_order_shape_from_parsed($parsed);`
- `includes/parcel_pack_costing.php`:410 — `$shape = parcel_pack_order_shape_from_parsed($parsed);`

## parcel_pack_tier_matches {#parcel_pack_tier_matches}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_pick_honey_tier_key {#parcel_pack_pick_honey_tier_key}

**Callers (1):**

- `includes/parcel_pack_costing.php`:158 — `$honeyKey = parcel_pack_pick_honey_tier_key($shape);`

## parcel_pack_pick_dept_tier_key {#parcel_pack_pick_dept_tier_key}

**Callers (1):**

- `includes/parcel_pack_costing.php`:162 — `$deptKey = parcel_pack_pick_dept_tier_key($shape);`

## parcel_pack_pick_cacao_tier_key {#parcel_pack_pick_cacao_tier_key}

**Callers (1):**

- `includes/parcel_pack_costing.php`:166 — `$cacaoKey = parcel_pack_pick_cacao_tier_key($shape);`

## parcel_pack_resolve_tier_keys_from_shape {#parcel_pack_resolve_tier_keys_from_shape}

**Callers (2):**

- `includes/parcel_pack_costing.php`:185 — `return parcel_pack_resolve_tier_keys_from_shape($shape);`
- `includes/parcel_pack_costing.php`:411 — `$tierKeys = parcel_pack_resolve_tier_keys_from_shape($shape);`

## parcel_pack_resolve_tier_keys_from_items_raw {#parcel_pack_resolve_tier_keys_from_items_raw}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## parcel_pack_fetch_tier_by_key {#parcel_pack_fetch_tier_by_key}

**Tables:** `parcel_pack_tiers`

**Callers (2):**

- `includes/parcel_pack_costing.php`:314 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, $activeOnly);`
- `includes/parcel_pack_costing.php`:341 — `$tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, false);`

## parcel_pack_fetch_tier_lines {#parcel_pack_fetch_tier_lines}

**Tables:** `parcel_pack_tier_lines`, `stocks`

**Callers (2):**

- `includes/parcel_pack_costing.php`:296 — `$lines = parcel_pack_fetch_tier_lines($pdo, $tierId);`
- `includes/parcel_pack_costing.php`:504 — `$lines = parcel_pack_fetch_tier_lines($pdo, $tierId);`

## parcel_pack_line_unit_cost {#parcel_pack_line_unit_cost}

**Callers (1):**

- `includes/parcel_pack_costing.php`:228 — `'unit_cost' => parcel_pack_line_unit_cost($pdo, $row),`

## parcel_pack_sum_line_costs {#parcel_pack_sum_line_costs}

**Callers (1):**

- `includes/parcel_pack_costing.php`:297 — `$sum = parcel_pack_sum_line_costs($lines);`

## parcel_pack_tier_cost_estimate {#parcel_pack_tier_cost_estimate}

**Tables:** `parcel_pack_tiers`

**Callers (5):**

- `api/v1/costing.php`:527 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`
- `api/v1/costing.php`:555 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`
- `includes/parcel_pack_costing.php`:319 — `return parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:356 — `$est = parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);`
- `includes/parcel_pack_costing.php`:383 — `$estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);`

## parcel_pack_tier_cost_estimate_by_key {#parcel_pack_tier_cost_estimate_by_key}

**Callers (4):**

- `includes/parcel_pack_costing.php`:418 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, $key);`
- `includes/parcel_pack_costing.php`:525 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, 'dept_1_2_units');`
- `includes/parcel_pack_costing.php`:535 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, 'cacao_1_2_jars');`
- `includes/parcel_pack_costing.php`:546 — `$est = parcel_pack_tier_cost_estimate_by_key($pdo, 'honey_1x_1L');`

## parcel_pack_tier_is_placeholder {#parcel_pack_tier_is_placeholder}

**Callers (1):**

- `includes/parcel_pack_costing.php`:345 — `if (parcel_pack_tier_is_placeholder($tier)) {`

## parcel_pack_preview_tier_by_key {#parcel_pack_preview_tier_by_key}

**Callers (1):**

- `api/v1/costing.php`:575 — `api_ok(parcel_pack_preview_tier_by_key($pdo, $tierKey));`

## parcel_pack_list_tiers {#parcel_pack_list_tiers}

**Tables:** `parcel_pack_tiers`

**Callers (1):**

- `api/v1/costing.php`:475 — `'tiers' => parcel_pack_list_tiers($pdo, true),`

## parcel_pack_preview_from_items_raw {#parcel_pack_preview_from_items_raw}

**Callers (1):**

- `api/v1/costing.php`:580 — `api_ok(parcel_pack_preview_from_items_raw($pdo, $itemsRaw));`

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

