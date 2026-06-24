---
title: fulfillment_packing
source: includes/fulfillment_packing.php
generated: 2026-06-24T03:46:01+02:00
type: module
function_count: 33
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
- `includes/fulfillment_packing.php`:285 — `if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {`
- `includes/fulfillment_packing.php`:463 — `if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {`

## fulfillment_packing_is_bubble_generic_name {#fulfillment_packing_is_bubble_generic_name}

**Callers (3):**

- `includes/fulfillment_packing.php`:283 — `if (fulfillment_packing_is_bubble_generic_name($name)) {`
- `includes/fulfillment_packing.php`:461 — `if (fulfillment_packing_is_bubble_generic_name($name)) {`
- `includes/fulfillment_packing.php`:781 — `|| fulfillment_packing_is_bubble_generic_name((string) $row['name'])) {`

## fulfillment_packing_is_bubble_sized_name {#fulfillment_packing_is_bubble_sized_name}

**Callers (4):**

- `includes/fulfillment_packing.php`:96 — `if (!fulfillment_packing_is_bubble_sized_name((string) ($row['name'] ?? ''))) {`
- `includes/fulfillment_packing.php`:133 — `$isBubbleSized = fulfillment_packing_is_bubble_sized_name($name);`
- `includes/fulfillment_packing.php`:289 — `} elseif (fulfillment_packing_is_bubble_sized_name($name)) {`
- `includes/fulfillment_packing.php`:449 — `if (fulfillment_packing_is_bubble_sized_name($name)) {`

## fulfillment_packing_bubble_sized_stock_name {#fulfillment_packing_bubble_sized_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:40 — `$name = fulfillment_packing_bubble_sized_stock_name($widthCm);`
- `includes/fulfillment_packing.php`:482 — `'stock_name' => fulfillment_packing_bubble_sized_stock_name($width),`

## fulfillment_packing_bubble_sized_stock_id {#fulfillment_packing_bubble_sized_stock_id}

**Tables:** `stocks`

**Callers (2):**

- `includes/fulfillment_packing.php`:288 — `$stockId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);`
- `includes/fulfillment_packing.php`:466 — `$stagingId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);`

## fulfillment_packing_foam_generic_stock_name {#fulfillment_packing_foam_generic_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:60 — `return strtolower(trim($name)) === strtolower(fulfillment_packing_foam_generic_stock_name());`
- `includes/fulfillment_packing.php`:70 — `$name = fulfillment_packing_foam_generic_stock_name();`

## fulfillment_packing_is_foam_generic_name {#fulfillment_packing_is_foam_generic_name}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_is_foam_sized_name {#fulfillment_packing_is_foam_sized_name}

**Callers (4):**

- `includes/fulfillment_packing.php`:291 — `} elseif (fulfillment_packing_is_foam_sized_name($name)) {`
- `includes/fulfillment_packing.php`:362 — `if ($unit === 'foam_piece' && fulfillment_packing_is_foam_sized_name($stockName)) {`
- `includes/fulfillment_packing.php`:455 — `if (fulfillment_packing_is_foam_sized_name($name)) {`
- `includes/fulfillment_packing.php`:652 — `if ($lineUnit === 'foam_piece' && fulfillment_packing_is_foam_sized_name((string) $row['name'])) {`

## fulfillment_packing_foam_generic_stock_id {#fulfillment_packing_foam_generic_stock_id}

**Tables:** `stocks`

**Callers (2):**

- `includes/fulfillment_packing.php`:364 — `$lookupId = fulfillment_packing_foam_generic_stock_id($pdo);`
- `includes/fulfillment_packing.php`:653 — `$stagingStockId = fulfillment_packing_foam_generic_stock_id($pdo);`

## material_inventory_role_is_tl {#material_inventory_role_is_tl}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_staging_remaining {#fulfillment_packing_staging_remaining}

**Callers (5):**

- `includes/fulfillment_packing.php`:132 — `$remainingRoll = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:259 — `'remaining_qty' => fulfillment_packing_staging_remaining($issued, $consumed, $damaged),`
- `includes/fulfillment_packing.php`:675 — `$remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:823 — `$remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:853 — `$remainingRoll = fulfillment_packing_staging_remaining($issued, $consumed, $newDamaged);`

## fulfillment_packing_bubble_piece_yield_from_row {#fulfillment_packing_bubble_piece_yield_from_row}

**Callers (4):**

- `includes/fulfillment_packing.php`:131 — `$yield = fulfillment_packing_bubble_piece_yield_from_row($row);`
- `includes/fulfillment_packing.php`:168 — `$yield = fulfillment_packing_bubble_piece_yield_from_row($stockRow);`
- `includes/fulfillment_packing.php`:785 — `$yield = fulfillment_packing_bubble_piece_yield_from_row($row);`
- `includes/fulfillment_packing.php`:904 — `$yield = fulfillment_packing_bubble_piece_yield_from_row([`

## fulfillment_packing_staging_piece_qty {#fulfillment_packing_staging_piece_qty}

**Callers (9):**

- `includes/fulfillment_packing.php`:145 — `$out['issued_qty'] = fulfillment_packing_staging_piece_qty($issued, $yield);`
- `includes/fulfillment_packing.php`:146 — `$out['consumed_qty'] = fulfillment_packing_staging_piece_qty($consumed, $yield);`
- `includes/fulfillment_packing.php`:147 — `$out['damaged_qty'] = fulfillment_packing_staging_piece_qty($damaged, $yield);`
- `includes/fulfillment_packing.php`:148 — `$out['remaining_qty'] = fulfillment_packing_staging_piece_qty($remainingRoll, $yield);`
- `includes/fulfillment_packing.php`:171 — `'qty' => fulfillment_packing_staging_piece_qty($rollQty, $yield),`
- `includes/fulfillment_packing.php`:826 — `? fulfillment_packing_staging_piece_qty($remaining, $yield) . ' pc'`
- `includes/fulfillment_packing.php`:860 — `'damaged_qty' => fulfillment_packing_staging_piece_qty($newDamaged, $yield),`
- `includes/fulfillment_packing.php`:861 — `'remaining_qty' => fulfillment_packing_staging_piece_qty($remainingRoll, $yield),`
- `includes/fulfillment_packing.php`:910 — `'qty' => fulfillment_packing_staging_piece_qty($rollQty, $yield),`

## fulfillment_packing_pieces_to_staging_rolls {#fulfillment_packing_pieces_to_staging_rolls}

**Callers (1):**

- `includes/fulfillment_packing.php`:790 — `$rollQty = fulfillment_packing_pieces_to_staging_rolls($qty, $yield);`

## fulfillment_packing_staging_balance_display_row {#fulfillment_packing_staging_balance_display_row}

**Callers (1):**

- `includes/fulfillment_packing.php`:738 — `$rows[] = fulfillment_packing_staging_balance_display_row($row, $issued, $consumed, $damaged);`

## fulfillment_packing_issue_line_display {#fulfillment_packing_issue_line_display}

**Callers (2):**

- `includes/fulfillment_packing.php`:597 — `? fulfillment_packing_issue_line_display($stagingRow, $qty, $lineUnit)`
- `includes/fulfillment_packing.php`:986 — `$display = fulfillment_packing_issue_line_display([`

## fulfillment_packing_is_fulfillment_stock_name {#fulfillment_packing_is_fulfillment_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:490 — `if (!fulfillment_packing_is_fulfillment_stock_name($name)) {`
- `includes/fulfillment_packing.php`:780 — `if (!fulfillment_packing_is_fulfillment_stock_name((string) $row['name'])`

## fulfillment_packing_tables_ready {#fulfillment_packing_tables_ready}

**Tables:** `fulfillment_packing_balances`

**Callers (9):**

- `includes/fulfillment_packing.php`:238 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:429 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:637 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:707 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:757 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:879 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:938 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1123 — `if (fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1189 — `if (fulfillment_packing_tables_ready($pdo)) {`

## material_inventory_role_can_issue_packing {#material_inventory_role_can_issue_packing}

**Callers (3):**

- `api/v1/fulfillment_packing.php`:12 — `if (!material_inventory_role_can_issue_packing($role)) {`
- `api/v1/materials.php`:53 — `if ($category !== 'packaging' || !material_inventory_role_can_issue_packing($role)) {`
- `includes/fulfillment_packing.php`:227 — `return material_inventory_role_can_issue_packing($role);`

## material_inventory_role_can_edit_packaging_price {#material_inventory_role_can_edit_packaging_price}

**Callers (2):**

- `api/v1/materials.php`:353 — `'can_edit_price' => material_inventory_role_can_edit_packaging_price($role),`
- `api/v1/materials.php`:660 — `if (!material_inventory_role_can_edit_packaging_price($role)) {`

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

- `includes/fulfillment_packing.php`:310 — `$lines = fulfillment_packing_estimate_lines($pdo, $lines);`

## fulfillment_packing_estimate_parcels {#fulfillment_packing_estimate_parcels}

**Callers (2):**

- `api/v1/fulfillment_packing.php`:77 — `api_ok(['estimate' => fulfillment_packing_estimate_parcels($pdo, $normalized)]);`
- `includes/fulfillment_packing.php`:511 — `$estimate = fulfillment_packing_estimate_parcels($pdo, $lines);`

## fulfillment_packing_issue_create {#fulfillment_packing_issue_create}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `fulfillment_packing_balances`, `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:107 — `$result = fulfillment_packing_issue_create($pdo, (int) $user['id'], $normalized, $note);`

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

- `api/v1/fulfillment_packing.php`:132 — `$result = fulfillment_packing_report_damage($pdo, (int) $user['id'], $stockId, $qty, $note, $qtyUnit);`

## fulfillment_packing_damage_list {#fulfillment_packing_damage_list}

**Tables:** `fulfillment_packing_damage_events`, `stocks`, `users`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:146 — `$result = fulfillment_packing_damage_list($pdo, $page, $perPage);`

## fulfillment_packing_issues_list {#fulfillment_packing_issues_list}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `users`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:44 — `$result = fulfillment_packing_issues_list($pdo, $page, $perPage);`

## material_packaging_record_invoice_price {#material_packaging_record_invoice_price}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:681 — `$result = material_packaging_record_invoice_price(`

