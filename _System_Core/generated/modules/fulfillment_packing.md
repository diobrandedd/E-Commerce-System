---
title: fulfillment_packing
source: includes/fulfillment_packing.php
generated: 2026-06-18T10:52:30+02:00
type: module
function_count: 28
---


# fulfillment_packing

> Source: `includes/fulfillment_packing.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/material_inventory.php`
- `includes/parcel_pack_costing.php`
- `includes/rts_schema.php`

## fulfillment_packing_bubble_generic_stock_name {#fulfillment_packing_bubble_generic_stock_name}

**Callers (1):**

- `includes/fulfillment_packing.php`:22 — `return strtolower(trim($name)) === strtolower(fulfillment_packing_bubble_generic_stock_name());`

## fulfillment_packing_bubble_widths_cm {#fulfillment_packing_bubble_widths_cm}

**Callers (3):**

- `includes/fulfillment_packing.php`:37 — `if (!in_array($widthCm, fulfillment_packing_bubble_widths_cm(), true)) {`
- `includes/fulfillment_packing.php`:194 — `if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {`
- `includes/fulfillment_packing.php`:372 — `if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {`

## fulfillment_packing_is_bubble_generic_name {#fulfillment_packing_is_bubble_generic_name}

**Callers (3):**

- `includes/fulfillment_packing.php`:192 — `if (fulfillment_packing_is_bubble_generic_name($name)) {`
- `includes/fulfillment_packing.php`:370 — `if (fulfillment_packing_is_bubble_generic_name($name)) {`
- `includes/fulfillment_packing.php`:693 — `|| fulfillment_packing_is_bubble_generic_name((string) $row['name'])) {`

## fulfillment_packing_is_bubble_sized_name {#fulfillment_packing_is_bubble_sized_name}

**Callers (3):**

- `includes/fulfillment_packing.php`:198 — `} elseif (fulfillment_packing_is_bubble_sized_name($name)) {`
- `includes/fulfillment_packing.php`:358 — `if (fulfillment_packing_is_bubble_sized_name($name)) {`
- `includes/fulfillment_packing.php`:649 — `'is_bubble_sized' => fulfillment_packing_is_bubble_sized_name((string) $row['name']),`

## fulfillment_packing_bubble_sized_stock_name {#fulfillment_packing_bubble_sized_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:40 — `$name = fulfillment_packing_bubble_sized_stock_name($widthCm);`
- `includes/fulfillment_packing.php`:391 — `'stock_name' => fulfillment_packing_bubble_sized_stock_name($width),`

## fulfillment_packing_bubble_sized_stock_id {#fulfillment_packing_bubble_sized_stock_id}

**Tables:** `stocks`

**Callers (2):**

- `includes/fulfillment_packing.php`:197 — `$stockId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);`
- `includes/fulfillment_packing.php`:375 — `$stagingId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);`

## fulfillment_packing_foam_generic_stock_name {#fulfillment_packing_foam_generic_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:60 — `return strtolower(trim($name)) === strtolower(fulfillment_packing_foam_generic_stock_name());`
- `includes/fulfillment_packing.php`:70 — `$name = fulfillment_packing_foam_generic_stock_name();`

## fulfillment_packing_is_foam_generic_name {#fulfillment_packing_is_foam_generic_name}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_is_foam_sized_name {#fulfillment_packing_is_foam_sized_name}

**Callers (4):**

- `includes/fulfillment_packing.php`:200 — `} elseif (fulfillment_packing_is_foam_sized_name($name)) {`
- `includes/fulfillment_packing.php`:271 — `if ($unit === 'foam_piece' && fulfillment_packing_is_foam_sized_name($stockName)) {`
- `includes/fulfillment_packing.php`:364 — `if (fulfillment_packing_is_foam_sized_name($name)) {`
- `includes/fulfillment_packing.php`:555 — `if ($lineUnit === 'foam_piece' && fulfillment_packing_is_foam_sized_name((string) $row['name'])) {`

## fulfillment_packing_foam_generic_stock_id {#fulfillment_packing_foam_generic_stock_id}

**Tables:** `stocks`

**Callers (2):**

- `includes/fulfillment_packing.php`:273 — `$lookupId = fulfillment_packing_foam_generic_stock_id($pdo);`
- `includes/fulfillment_packing.php`:556 — `$stagingStockId = fulfillment_packing_foam_generic_stock_id($pdo);`

## material_inventory_role_is_tl {#material_inventory_role_is_tl}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_staging_remaining {#fulfillment_packing_staging_remaining}

**Callers (5):**

- `includes/fulfillment_packing.php`:168 — `'remaining_qty' => fulfillment_packing_staging_remaining($issued, $consumed, $damaged),`
- `includes/fulfillment_packing.php`:578 — `$remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:648 — `'remaining_qty' => fulfillment_packing_staging_remaining($issued, $consumed, $damaged),`
- `includes/fulfillment_packing.php`:711 — `$remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:742 — `'remaining_qty' => fulfillment_packing_staging_remaining($issued, $consumed, $newDamaged),`

## fulfillment_packing_is_fulfillment_stock_name {#fulfillment_packing_is_fulfillment_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:399 — `if (!fulfillment_packing_is_fulfillment_stock_name($name)) {`
- `includes/fulfillment_packing.php`:692 — `if (!fulfillment_packing_is_fulfillment_stock_name((string) $row['name'])`

## fulfillment_packing_tables_ready {#fulfillment_packing_tables_ready}

**Tables:** `fulfillment_packing_balances`

**Callers (9):**

- `includes/fulfillment_packing.php`:147 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:338 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:540 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:610 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:668 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:750 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:791 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1123 — `if (fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1189 — `if (fulfillment_packing_tables_ready($pdo)) {`

## material_inventory_role_can_issue_packing {#material_inventory_role_can_issue_packing}

**Callers (2):**

- `api/v1/fulfillment_packing.php`:12 — `if (!material_inventory_role_can_issue_packing($role)) {`
- `includes/fulfillment_packing.php`:136 — `return material_inventory_role_can_issue_packing($role);`

## material_inventory_role_can_edit_packaging_price {#material_inventory_role_can_edit_packaging_price}

**Callers (2):**

- `api/v1/materials.php`:340 — `'can_edit_price' => material_inventory_role_can_edit_packaging_price($role),`
- `api/v1/materials.php`:647 — `if (!material_inventory_role_can_edit_packaging_price($role)) {`

## material_inventory_role_can_view_packing_staging {#material_inventory_role_can_view_packing_staging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_view_on_hold_parcels {#material_inventory_role_can_view_on_hold_parcels}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_get_balance {#fulfillment_packing_get_balance}

**Tables:** `fulfillment_packing_balances`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_estimate_lines {#fulfillment_packing_estimate_lines}

**Callers (1):**

- `includes/fulfillment_packing.php`:219 — `$lines = fulfillment_packing_estimate_lines($pdo, $lines);`

## fulfillment_packing_estimate_parcels {#fulfillment_packing_estimate_parcels}

**Callers (2):**

- `api/v1/fulfillment_packing.php`:77 — `api_ok(['estimate' => fulfillment_packing_estimate_parcels($pdo, $normalized)]);`
- `includes/fulfillment_packing.php`:420 — `$estimate = fulfillment_packing_estimate_parcels($pdo, $lines);`

## fulfillment_packing_issue_create {#fulfillment_packing_issue_create}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `fulfillment_packing_balances`, `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:108 — `$result = fulfillment_packing_issue_create($pdo, (int) $user['id'], $normalized, $note);`

## fulfillment_packing_consume_line {#fulfillment_packing_consume_line}

**Tables:** `fulfillment_packing_balances`

**Callers (1):**

- `includes/material_inventory.php`:1124 — `$result = fulfillment_packing_consume_line(`

## fulfillment_packing_balances_list {#fulfillment_packing_balances_list}

**Tables:** `stocks`, `fulfillment_packing_balances`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:33 — `$rows = fulfillment_packing_balances_list($pdo);`

## fulfillment_packing_report_damage {#fulfillment_packing_report_damage}

**Tables:** `fulfillment_packing_balances`, `fulfillment_packing_damage_events`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:135 — `$result = fulfillment_packing_report_damage($pdo, (int) $user['id'], $stockId, $qty, $note);`

## fulfillment_packing_damage_list {#fulfillment_packing_damage_list}

**Tables:** `fulfillment_packing_damage_events`, `stocks`, `users`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:149 — `$result = fulfillment_packing_damage_list($pdo, $page, $perPage);`

## fulfillment_packing_issues_list {#fulfillment_packing_issues_list}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `users`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:44 — `$result = fulfillment_packing_issues_list($pdo, $page, $perPage);`

## material_packaging_record_invoice_price {#material_packaging_record_invoice_price}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:668 — `$result = material_packaging_record_invoice_price(`

