---
title: fulfillment_packing
source: includes/fulfillment_packing.php
generated: 2026-09-16T07:30:50+02:00
type: module
function_count: 33
---


# fulfillment_packing

> Source: `includes/fulfillment_packing.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/material_inventory.php`
- `includes/permissions.php`
- `includes/parcel_pack_costing.php`
- `includes/rts_schema.php`

## fulfillment_packing_bubble_generic_stock_name {#fulfillment_packing_bubble_generic_stock_name}

**Callers (1):**

- `includes/fulfillment_packing.php`:23 — `return strtolower(trim($name)) === strtolower(fulfillment_packing_bubble_generic_stock_name());`

## fulfillment_packing_bubble_widths_cm {#fulfillment_packing_bubble_widths_cm}

**Callers (3):**

- `includes/fulfillment_packing.php`:38 — `if (!in_array($widthCm, fulfillment_packing_bubble_widths_cm(), true)) {`
- `includes/fulfillment_packing.php`:286 — `if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {`
- `includes/fulfillment_packing.php`:464 — `if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {`

## fulfillment_packing_is_bubble_generic_name {#fulfillment_packing_is_bubble_generic_name}

**Callers (3):**

- `includes/fulfillment_packing.php`:284 — `if (fulfillment_packing_is_bubble_generic_name($name)) {`
- `includes/fulfillment_packing.php`:462 — `if (fulfillment_packing_is_bubble_generic_name($name)) {`
- `includes/fulfillment_packing.php`:782 — `|| fulfillment_packing_is_bubble_generic_name((string) $row['name'])) {`

## fulfillment_packing_is_bubble_sized_name {#fulfillment_packing_is_bubble_sized_name}

**Callers (4):**

- `includes/fulfillment_packing.php`:97 — `if (!fulfillment_packing_is_bubble_sized_name((string) ($row['name'] ?? ''))) {`
- `includes/fulfillment_packing.php`:134 — `$isBubbleSized = fulfillment_packing_is_bubble_sized_name($name);`
- `includes/fulfillment_packing.php`:290 — `} elseif (fulfillment_packing_is_bubble_sized_name($name)) {`
- `includes/fulfillment_packing.php`:450 — `if (fulfillment_packing_is_bubble_sized_name($name)) {`

## fulfillment_packing_bubble_sized_stock_name {#fulfillment_packing_bubble_sized_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:41 — `$name = fulfillment_packing_bubble_sized_stock_name($widthCm);`
- `includes/fulfillment_packing.php`:483 — `'stock_name' => fulfillment_packing_bubble_sized_stock_name($width),`

## fulfillment_packing_bubble_sized_stock_id {#fulfillment_packing_bubble_sized_stock_id}

**Tables:** `stocks`

**Callers (2):**

- `includes/fulfillment_packing.php`:289 — `$stockId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);`
- `includes/fulfillment_packing.php`:467 — `$stagingId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);`

## fulfillment_packing_foam_generic_stock_name {#fulfillment_packing_foam_generic_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:61 — `return strtolower(trim($name)) === strtolower(fulfillment_packing_foam_generic_stock_name());`
- `includes/fulfillment_packing.php`:71 — `$name = fulfillment_packing_foam_generic_stock_name();`

## fulfillment_packing_is_foam_generic_name {#fulfillment_packing_is_foam_generic_name}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_is_foam_sized_name {#fulfillment_packing_is_foam_sized_name}

**Callers (4):**

- `includes/fulfillment_packing.php`:292 — `} elseif (fulfillment_packing_is_foam_sized_name($name)) {`
- `includes/fulfillment_packing.php`:363 — `if ($unit === 'foam_piece' && fulfillment_packing_is_foam_sized_name($stockName)) {`
- `includes/fulfillment_packing.php`:456 — `if (fulfillment_packing_is_foam_sized_name($name)) {`
- `includes/fulfillment_packing.php`:653 — `if ($lineUnit === 'foam_piece' && fulfillment_packing_is_foam_sized_name((string) $row['name'])) {`

## fulfillment_packing_foam_generic_stock_id {#fulfillment_packing_foam_generic_stock_id}

**Tables:** `stocks`

**Callers (2):**

- `includes/fulfillment_packing.php`:365 — `$lookupId = fulfillment_packing_foam_generic_stock_id($pdo);`
- `includes/fulfillment_packing.php`:654 — `$stagingStockId = fulfillment_packing_foam_generic_stock_id($pdo);`

## material_inventory_role_is_tl {#material_inventory_role_is_tl}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## fulfillment_packing_staging_remaining {#fulfillment_packing_staging_remaining}

**Callers (5):**

- `includes/fulfillment_packing.php`:133 — `$remainingRoll = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:260 — `'remaining_qty' => fulfillment_packing_staging_remaining($issued, $consumed, $damaged),`
- `includes/fulfillment_packing.php`:676 — `$remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:824 — `$remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);`
- `includes/fulfillment_packing.php`:854 — `$remainingRoll = fulfillment_packing_staging_remaining($issued, $consumed, $newDamaged);`

## fulfillment_packing_bubble_piece_yield_from_row {#fulfillment_packing_bubble_piece_yield_from_row}

**Callers (4):**

- `includes/fulfillment_packing.php`:132 — `$yield = fulfillment_packing_bubble_piece_yield_from_row($row);`
- `includes/fulfillment_packing.php`:169 — `$yield = fulfillment_packing_bubble_piece_yield_from_row($stockRow);`
- `includes/fulfillment_packing.php`:786 — `$yield = fulfillment_packing_bubble_piece_yield_from_row($row);`
- `includes/fulfillment_packing.php`:905 — `$yield = fulfillment_packing_bubble_piece_yield_from_row([`

## fulfillment_packing_staging_piece_qty {#fulfillment_packing_staging_piece_qty}

**Callers (9):**

- `includes/fulfillment_packing.php`:146 — `$out['issued_qty'] = fulfillment_packing_staging_piece_qty($issued, $yield);`
- `includes/fulfillment_packing.php`:147 — `$out['consumed_qty'] = fulfillment_packing_staging_piece_qty($consumed, $yield);`
- `includes/fulfillment_packing.php`:148 — `$out['damaged_qty'] = fulfillment_packing_staging_piece_qty($damaged, $yield);`
- `includes/fulfillment_packing.php`:149 — `$out['remaining_qty'] = fulfillment_packing_staging_piece_qty($remainingRoll, $yield);`
- `includes/fulfillment_packing.php`:172 — `'qty' => fulfillment_packing_staging_piece_qty($rollQty, $yield),`
- `includes/fulfillment_packing.php`:827 — `? fulfillment_packing_staging_piece_qty($remaining, $yield) . ' pc'`
- `includes/fulfillment_packing.php`:861 — `'damaged_qty' => fulfillment_packing_staging_piece_qty($newDamaged, $yield),`
- `includes/fulfillment_packing.php`:862 — `'remaining_qty' => fulfillment_packing_staging_piece_qty($remainingRoll, $yield),`
- `includes/fulfillment_packing.php`:911 — `'qty' => fulfillment_packing_staging_piece_qty($rollQty, $yield),`

## fulfillment_packing_pieces_to_staging_rolls {#fulfillment_packing_pieces_to_staging_rolls}

**Callers (1):**

- `includes/fulfillment_packing.php`:791 — `$rollQty = fulfillment_packing_pieces_to_staging_rolls($qty, $yield);`

## fulfillment_packing_staging_balance_display_row {#fulfillment_packing_staging_balance_display_row}

**Callers (1):**

- `includes/fulfillment_packing.php`:739 — `$rows[] = fulfillment_packing_staging_balance_display_row($row, $issued, $consumed, $damaged);`

## fulfillment_packing_issue_line_display {#fulfillment_packing_issue_line_display}

**Callers (2):**

- `includes/fulfillment_packing.php`:598 — `? fulfillment_packing_issue_line_display($stagingRow, $qty, $lineUnit)`
- `includes/fulfillment_packing.php`:987 — `$display = fulfillment_packing_issue_line_display([`

## fulfillment_packing_is_fulfillment_stock_name {#fulfillment_packing_is_fulfillment_stock_name}

**Callers (2):**

- `includes/fulfillment_packing.php`:491 — `if (!fulfillment_packing_is_fulfillment_stock_name($name)) {`
- `includes/fulfillment_packing.php`:781 — `if (!fulfillment_packing_is_fulfillment_stock_name((string) $row['name'])`

## fulfillment_packing_tables_ready {#fulfillment_packing_tables_ready}

**Tables:** `fulfillment_packing_balances`

**Callers (9):**

- `includes/fulfillment_packing.php`:239 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:430 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:638 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:708 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:758 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:880 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:939 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1147 — `if (fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:1213 — `if (fulfillment_packing_tables_ready($pdo)) {`

## material_inventory_role_can_issue_packing {#material_inventory_role_can_issue_packing}

**Callers (2):**

- `api/v1/fulfillment_packing.php`:12 — `if (!material_inventory_role_can_issue_packing($user)) {`
- `api/v1/materials.php`:53 — `if ($category !== 'packaging' || !material_inventory_role_can_issue_packing($user)) {`

## material_inventory_role_can_edit_packaging_price {#material_inventory_role_can_edit_packaging_price}

**Callers (2):**

- `api/v1/materials.php`:353 — `'can_edit_price' => material_inventory_role_can_edit_packaging_price($user),`
- `api/v1/materials.php`:672 — `if (!material_inventory_role_can_edit_packaging_price($user)) {`

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

- `includes/fulfillment_packing.php`:311 — `$lines = fulfillment_packing_estimate_lines($pdo, $lines);`

## fulfillment_packing_estimate_parcels {#fulfillment_packing_estimate_parcels}

**Callers (2):**

- `api/v1/fulfillment_packing.php`:77 — `api_ok(['estimate' => fulfillment_packing_estimate_parcels($pdo, $normalized)]);`
- `includes/fulfillment_packing.php`:512 — `$estimate = fulfillment_packing_estimate_parcels($pdo, $lines);`

## fulfillment_packing_issue_create {#fulfillment_packing_issue_create}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `fulfillment_packing_balances`, `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:107 — `$result = fulfillment_packing_issue_create($pdo, (int) $user['id'], $normalized, $note);`

## fulfillment_packing_consume_line {#fulfillment_packing_consume_line}

**Tables:** `fulfillment_packing_balances`

**Callers (1):**

- `includes/material_inventory.php`:1148 — `$result = fulfillment_packing_consume_line(`

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

- `api/v1/materials.php`:693 — `$result = material_packaging_record_invoice_price(`

