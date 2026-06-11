---
title: material_inventory
source: includes/material_inventory.php
generated: 2026-06-11T11:15:35+02:00
type: module
function_count: 38
---


# material_inventory

> Source: `includes/material_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`

## material_inventory_role_can_view {#material_inventory_role_can_view}

**Callers (1):**

- `api/v1/materials.php`:32 — `if (!material_inventory_role_can_view((string) $user['role'])) {`

## material_inventory_role_can_catalog {#material_inventory_role_can_catalog}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_adjust {#material_inventory_role_can_adjust}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_request {#material_inventory_role_can_request}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_request_packaging {#material_inventory_role_can_request_packaging}

**Callers (1):**

- `api/v1/materials.php`:205 — `'can_submit_packaging' => material_inventory_role_can_request_packaging($role),`

## material_inventory_role_can_set_packaging {#material_inventory_role_can_set_packaging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_stock_in_supply {#material_inventory_role_can_stock_in_supply}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_request_supply {#material_inventory_role_can_request_supply}

**Callers (2):**

- `api/v1/materials.php`:165 — `if (!material_inventory_role_can_request_supply($role)) {`
- `api/v1/materials.php`:206 — `'can_submit_supply' => material_inventory_role_can_request_supply($role),`

## material_inventory_role_can_create_supply_catalog {#material_inventory_role_can_create_supply_catalog}

**Callers (1):**

- `api/v1/materials.php`:70 — `if (!material_inventory_role_can_create_supply_catalog($role)) {`

## material_supply_canonical_dept_id {#material_supply_canonical_dept_id}

**Tables:** `departments`

**Callers (1):**

- `includes/material_inventory.php`:164 — `$deptId = material_supply_canonical_dept_id($pdo);`

## material_inventory_has_columns {#material_inventory_has_columns}

**Callers (4):**

- `api/v1/costing.php`:55 — `$hasCategory = material_inventory_has_columns($pdo);`
- `includes/material_inventory.php`:107 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:152 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:1047 — `if (material_inventory_has_columns($pdo)) {`

## material_inventory_fetch_stock {#material_inventory_fetch_stock}

**Tables:** `stocks`, `departments`

**Callers (18):**

- `api/v1/materials.php`:152 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:307 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:329 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:358 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:416 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/dept_production.php`:252 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:193 — `$row = material_inventory_fetch_stock($pdo, $id);`
- `includes/material_inventory.php`:208 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:242 — `$updated = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:267 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:321 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:458 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:538 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:643 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:723 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:784 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:837 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:935 — `$stock = material_inventory_fetch_stock($pdo, $stockId, true);`

## material_inventory_list {#material_inventory_list}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:45 — `$items = material_inventory_list(`

## material_inventory_create {#material_inventory_create}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:78 — `$row = material_inventory_create($pdo, (int) $user['id'], $name, $deptId, $category, $unit);`

## material_inventory_update {#material_inventory_update}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:100 — `$row = material_inventory_update($pdo, $stockId, $name, $unit, $isActive);`

## material_inventory_adjust {#material_inventory_adjust}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:126 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);`
- `includes/material_inventory.php`:958 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);`

## material_packaging_set_values {#material_packaging_set_values}

**Tables:** `stock_entries`, `stocks`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_parse_lot_batch_label {#material_parse_lot_batch_label}

**Callers (3):**

- `includes/material_inventory.php`:392 — `return material_parse_lot_batch_label($label);`
- `includes/material_inventory.php`:411 — `$parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);`
- `includes/material_inventory.php`:596 — `$parsed = material_parse_lot_batch_label($label);`

## material_packaging_parse_lot_batch_label {#material_packaging_parse_lot_batch_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_pool_unit_cost_from_lots {#material_pool_unit_cost_from_lots}

**Tables:** `stock_entries`

**Callers (3):**

- `includes/material_inventory.php`:436 — `return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);`
- `includes/material_inventory.php`:500 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`
- `includes/material_inventory.php`:685 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`

## material_packaging_pool_unit_cost_from_lots {#material_packaging_pool_unit_cost_from_lots}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_stock_in_lot {#material_packaging_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:427 — `$result = material_packaging_stock_in_lot(`

## material_packaging_set_on_hand {#material_packaging_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:366 — `$result = material_packaging_set_on_hand(`
- `includes/material_inventory.php`:944 — `$result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_pool_recent_entries {#material_pool_recent_entries}

**Tables:** `stock_entries`, `users`

**Callers (2):**

- `includes/material_inventory.php`:615 — `return material_pool_recent_entries($pdo, $stockId, $limit);`
- `includes/material_inventory.php`:621 — `return material_pool_recent_entries($pdo, $stockId, $limit);`

## material_packaging_recent_entries {#material_packaging_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:313 — `$entries = material_packaging_recent_entries($pdo, $stockId, 25);`

## material_supply_recent_entries {#material_supply_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:335 — `$entries = material_supply_recent_entries($pdo, $stockId, 25);`

## material_supply_stock_in_lot {#material_supply_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:436 — `$result = material_supply_stock_in_lot(`

## material_supply_set_on_hand {#material_supply_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:374 — `$result = material_supply_set_on_hand(`
- `includes/material_inventory.php`:956 — `$result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_supply_apply_issue {#material_supply_apply_issue}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:947 — `$result = material_supply_apply_issue(`

## material_adjustment_submit {#material_adjustment_submit}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:178 — `$id = material_adjustment_submit(`

## material_adjustment_list {#material_adjustment_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:200 — `$requests = material_adjustment_list($pdo, $requestedBy);`

## material_adjustment_summary {#material_adjustment_summary}

**Callers (1):**

- `includes/material_inventory.php`:894 — `$row['summary'] = material_adjustment_summary($row);`

## material_adjustment_approve {#material_adjustment_approve}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:220 — `material_adjustment_approve($pdo, $id, (int) $user['id']);`

## material_adjustment_reject {#material_adjustment_reject}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:241 — `material_adjustment_reject($pdo, $id, (int) $user['id'], $note);`

## material_adjustment_pending_count {#material_adjustment_pending_count}

**Tables:** `material_adjustment_requests`

**Callers (2):**

- `api/v1/honey.php`:343 — `$count += material_adjustment_pending_count($pdo);`
- `api/v1/materials.php`:257 — `api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);`

## material_adjustment_mark_viewed {#material_adjustment_mark_viewed}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:267 — `material_adjustment_mark_viewed(ims_pdo(), $id);`

## material_raw_snapshot {#material_raw_snapshot}

**Tables:** `honey_warehouse_rows`, `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:275 — `api_ok(material_raw_snapshot(ims_pdo()));`

## material_inventory_raw_stock_by_slug {#material_inventory_raw_stock_by_slug}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:283 — `$row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);`

