---
title: material_inventory
source: includes/material_inventory.php
generated: 2026-06-16T10:40:56+02:00
type: module
function_count: 48
---


# material_inventory

> Source: `includes/material_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/catalog_bootstrap.php`

## material_inventory_role_can_view {#material_inventory_role_can_view}

**Callers (1):**

- `api/v1/materials.php`:35 — `if (!material_inventory_role_can_view((string) $user['role'])) {`

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

- `api/v1/materials.php`:211 — `'can_submit_packaging' => material_inventory_role_can_request_packaging($role),`

## material_inventory_role_can_stock_in_packaging {#material_inventory_role_can_stock_in_packaging}

**Callers (1):**

- `api/v1/materials.php`:335 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($role),`

## material_inventory_role_can_deduct_packaging {#material_inventory_role_can_deduct_packaging}

**Callers (2):**

- `api/v1/materials.php`:336 — `'can_deduct' => material_inventory_role_can_deduct_packaging($role),`
- `api/v1/materials.php`:351 — `if (!material_inventory_role_can_deduct_packaging($role)) {`

## material_inventory_role_can_set_packaging_on_hand {#material_inventory_role_can_set_packaging_on_hand}

**Callers (2):**

- `api/v1/materials.php`:337 — `'can_set_on_hand' => material_inventory_role_can_set_packaging_on_hand($role),`
- `includes/material_inventory.php`:53 — `return material_inventory_role_can_set_packaging_on_hand($role);`

## material_inventory_role_can_set_packaging {#material_inventory_role_can_set_packaging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_stock_in_supply {#material_inventory_role_can_stock_in_supply}

**Callers (1):**

- `includes/material_inventory.php`:75 — `'can_stock_in' => material_inventory_role_can_stock_in_supply($role),`

## material_inventory_role_can_request_supply {#material_inventory_role_can_request_supply}

**Callers (2):**

- `api/v1/materials.php`:168 — `if (!material_inventory_role_can_request_supply($role)) {`
- `api/v1/materials.php`:212 — `'can_submit_supply' => material_inventory_role_can_request_supply($role),`

## material_inventory_role_can_issue_supply {#material_inventory_role_can_issue_supply}

**Callers (2):**

- `includes/material_inventory.php`:63 — `return material_inventory_role_can_issue_supply($role);`
- `includes/material_inventory.php`:76 — `'can_issue_request' => material_inventory_role_can_issue_supply($role),`

## material_supply_detail_permissions {#material_supply_detail_permissions}

**Callers (1):**

- `api/v1/materials.php`:446 — `'permissions' => material_supply_detail_permissions($role),`

## material_inventory_role_can_create_supply_catalog {#material_inventory_role_can_create_supply_catalog}

**Callers (2):**

- `api/v1/materials.php`:73 — `if (!material_inventory_role_can_create_supply_catalog($role)) {`
- `includes/material_inventory.php`:78 — `'can_create_catalog' => material_inventory_role_can_create_supply_catalog($role),`

## material_supply_canonical_dept_id {#material_supply_canonical_dept_id}

**Tables:** `departments`

**Callers (1):**

- `includes/material_inventory.php`:241 — `$deptId = material_supply_canonical_dept_id($pdo);`

## material_inventory_has_columns {#material_inventory_has_columns}

**Callers (4):**

- `api/v1/costing.php`:60 — `$hasCategory = material_inventory_has_columns($pdo);`
- `includes/material_inventory.php`:184 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:229 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:1303 — `if (material_inventory_has_columns($pdo)) {`

## material_inventory_has_pool_columns {#material_inventory_has_pool_columns}

**Callers (3):**

- `includes/honey_inventory.php`:391 — `if (!material_inventory_has_pool_columns($pdo)) {`
- `includes/material_inventory.php`:196 — `$poolCols = material_inventory_has_pool_columns($pdo)`
- `includes/material_inventory.php`:461 — `if (!material_inventory_has_pool_columns($pdo)) {`

## material_inventory_enrich_packaging_row {#material_inventory_enrich_packaging_row}

**Callers (3):**

- `api/v1/materials.php`:328 — `$row = material_inventory_enrich_packaging_row($row);`
- `api/v1/materials.php`:415 — `'stock' => $row !== null ? material_inventory_enrich_packaging_row($row) : null,`
- `includes/material_inventory.php`:212 — `$row = material_inventory_enrich_packaging_row($row);`

## material_inventory_fetch_stock {#material_inventory_fetch_stock}

**Tables:** `stocks`, `departments`

**Callers (21):**

- `api/v1/materials.php`:155 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:324 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:412 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:435 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:466 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:524 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/dept_production.php`:293 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:270 — `$row = material_inventory_fetch_stock($pdo, $id);`
- `includes/material_inventory.php`:285 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:319 — `$updated = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:344 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:398 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:467 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:586 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:674 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:734 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:849 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:934 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:995 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:1048 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:1162 — `$stock = material_inventory_fetch_stock($pdo, $stockId, true);`

## material_inventory_list {#material_inventory_list}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:48 — `$items = material_inventory_list(`

## material_inventory_create {#material_inventory_create}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:81 — `$row = material_inventory_create($pdo, (int) $user['id'], $name, $deptId, $category, $unit);`

## material_inventory_update {#material_inventory_update}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:103 — `$row = material_inventory_update($pdo, $stockId, $name, $unit, $isActive);`

## material_inventory_adjust {#material_inventory_adjust}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:129 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);`
- `includes/material_inventory.php`:1185 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);`

## material_packaging_set_values {#material_packaging_set_values}

**Tables:** `stock_entries`, `stocks`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_set_pool_yield {#material_packaging_set_pool_yield}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:404 — `$result = material_packaging_set_pool_yield(`

## material_parse_lot_batch_label {#material_parse_lot_batch_label}

**Callers (3):**

- `includes/material_inventory.php`:520 — `return material_parse_lot_batch_label($label);`
- `includes/material_inventory.php`:539 — `$parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);`
- `includes/material_inventory.php`:800 — `$parsed = material_parse_lot_batch_label($label);`

## material_packaging_parse_lot_batch_label {#material_packaging_parse_lot_batch_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_pool_unit_cost_from_lots {#material_pool_unit_cost_from_lots}

**Tables:** `stock_entries`

**Callers (3):**

- `includes/material_inventory.php`:564 — `return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);`
- `includes/material_inventory.php`:636 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`
- `includes/material_inventory.php`:896 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`

## material_packaging_pool_unit_cost_from_lots {#material_packaging_pool_unit_cost_from_lots}

**Callers (1):**

- `includes/parcel_pack_costing.php`:243 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`

## material_packaging_stock_in_lot {#material_packaging_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:535 — `$result = material_packaging_stock_in_lot(`

## material_packaging_set_on_hand {#material_packaging_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:474 — `$result = material_packaging_set_on_hand(`
- `includes/material_inventory.php`:1171 — `$result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_packaging_deduct_for_use {#material_packaging_deduct_for_use}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:369 — `$result = material_packaging_deduct_for_use(`

## material_pool_recent_entries {#material_pool_recent_entries}

**Tables:** `stock_entries`, `users`

**Callers (2):**

- `includes/material_inventory.php`:821 — `return material_pool_recent_entries($pdo, $stockId, $limit);`
- `includes/material_inventory.php`:827 — `return material_pool_recent_entries($pdo, $stockId, $limit);`

## material_packaging_recent_entries {#material_packaging_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:330 — `$entries = material_packaging_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_recent_entries {#material_supply_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:442 — `$entries = material_supply_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_stock_in_lot {#material_supply_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:544 — `$result = material_supply_stock_in_lot(`

## material_supply_set_on_hand {#material_supply_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:482 — `$result = material_supply_set_on_hand(`
- `includes/material_inventory.php`:1183 — `$result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_supply_apply_issue {#material_supply_apply_issue}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:1174 — `$result = material_supply_apply_issue(`

## material_adjustment_submit {#material_adjustment_submit}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:181 — `$id = material_adjustment_submit(`

## material_adjustment_list {#material_adjustment_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:206 — `$requests = material_adjustment_list($pdo, $requestedBy, $category ?: null, $requestAction ?: null);`

## material_adjustment_summary {#material_adjustment_summary}

**Callers (2):**

- `includes/material_inventory.php`:1121 — `$row['summary'] = material_adjustment_summary($row);`
- `includes/material_inventory.php`:1267 — `$row['summary'] = material_adjustment_summary($row);`

## material_adjustment_approve {#material_adjustment_approve}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:226 — `material_adjustment_approve($pdo, $id, (int) $user['id']);`

## material_adjustment_reject {#material_adjustment_reject}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:247 — `material_adjustment_reject($pdo, $id, (int) $user['id'], $note);`

## material_adjustment_pending_count {#material_adjustment_pending_count}

**Tables:** `material_adjustment_requests`

**Callers (2):**

- `api/v1/honey.php`:344 — `$count += material_adjustment_pending_count($pdo);`
- `api/v1/materials.php`:263 — `api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);`

## material_adjustment_mark_viewed {#material_adjustment_mark_viewed}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:273 — `material_adjustment_mark_viewed(ims_pdo(), $id);`

## material_adjustment_unread_list {#material_adjustment_unread_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:283 — `$requests = material_adjustment_unread_list(ims_pdo(), $limit);`

## material_raw_snapshot {#material_raw_snapshot}

**Tables:** `honey_warehouse_rows`, `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:291 — `api_ok(material_raw_snapshot(ims_pdo()));`

## material_inventory_raw_stock_by_slug {#material_inventory_raw_stock_by_slug}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:299 — `$row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);`

