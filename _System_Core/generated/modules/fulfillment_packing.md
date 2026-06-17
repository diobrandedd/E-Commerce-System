---
title: fulfillment_packing
source: includes/fulfillment_packing.php
generated: 2026-06-17T10:42:33+02:00
type: module
function_count: 13
---


# fulfillment_packing

> Source: `includes/fulfillment_packing.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/material_inventory.php`
- `includes/parcel_pack_costing.php`

## fulfillment_packing_is_fulfillment_stock_name {#fulfillment_packing_is_fulfillment_stock_name}

**Callers (1):**

- `includes/fulfillment_packing.php`:216 — `if (!fulfillment_packing_is_fulfillment_stock_name((string) $row['name'])) {`

## fulfillment_packing_tables_ready {#fulfillment_packing_tables_ready}

**Tables:** `fulfillment_packing_balances`

**Callers (7):**

- `includes/fulfillment_packing.php`:62 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:198 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:328 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:391 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/fulfillment_packing.php`:432 — `if (!fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:914 — `if (fulfillment_packing_tables_ready($pdo)) {`
- `includes/material_inventory.php`:979 — `if (fulfillment_packing_tables_ready($pdo)) {`

## material_inventory_role_can_issue_packing {#material_inventory_role_can_issue_packing}

**Callers (1):**

- `api/v1/fulfillment_packing.php`:12 — `if (!material_inventory_role_can_issue_packing($role)) {`

## material_inventory_role_can_edit_packaging_price {#material_inventory_role_can_edit_packaging_price}

**Callers (2):**

- `api/v1/materials.php`:338 — `'can_edit_price' => material_inventory_role_can_edit_packaging_price($role),`
- `api/v1/materials.php`:592 — `if (!material_inventory_role_can_edit_packaging_price($role)) {`

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

## fulfillment_packing_estimate_parcels {#fulfillment_packing_estimate_parcels}

**Callers (2):**

- `api/v1/fulfillment_packing.php`:74 — `api_ok(['estimate' => fulfillment_packing_estimate_parcels($pdo, $normalized)]);`
- `includes/fulfillment_packing.php`:234 — `$estimate = fulfillment_packing_estimate_parcels($pdo, $normalized);`

## fulfillment_packing_issue_create {#fulfillment_packing_issue_create}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `fulfillment_packing_balances`, `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:104 — `$result = fulfillment_packing_issue_create($pdo, (int) $user['id'], $normalized, $note);`

## fulfillment_packing_consume_line {#fulfillment_packing_consume_line}

**Tables:** `fulfillment_packing_balances`

**Callers (1):**

- `includes/material_inventory.php`:915 — `$result = fulfillment_packing_consume_line(`

## fulfillment_packing_balances_list {#fulfillment_packing_balances_list}

**Tables:** `stocks`, `fulfillment_packing_balances`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:31 — `$rows = fulfillment_packing_balances_list($pdo);`

## fulfillment_packing_issues_list {#fulfillment_packing_issues_list}

**Tables:** `fulfillment_packing_issues`, `fulfillment_packing_issue_lines`, `users`, `stocks`

**Callers (1):**

- `api/v1/fulfillment_packing.php`:42 — `$result = fulfillment_packing_issues_list($pdo, $page, $perPage);`

## material_packaging_record_invoice_price {#material_packaging_record_invoice_price}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:613 — `$result = material_packaging_record_invoice_price(`

