---
title: material_inventory
source: includes/material_inventory.php
generated: 2026-06-12T05:33:57+02:00
type: module
function_count: 45
---


# material_inventory

> Source: `includes/material_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/catalog_bootstrap.php`

## material_inventory_role_can_view {#material_inventory_role_can_view}

**Callers (1):**

- `api/v1/materials.php`:34 — `if (!material_inventory_role_can_view((string) $user['role'])) {`

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

- `api/v1/materials.php`:207 — `'can_submit_packaging' => material_inventory_role_can_request_packaging($role),`

## material_inventory_role_can_stock_in_packaging {#material_inventory_role_can_stock_in_packaging}

**Callers (1):**

- `api/v1/materials.php`:321 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($role),`

## material_inventory_role_can_deduct_packaging {#material_inventory_role_can_deduct_packaging}

**Callers (2):**

- `api/v1/materials.php`:322 — `'can_deduct' => material_inventory_role_can_deduct_packaging($role),`
- `api/v1/materials.php`:337 — `if (!material_inventory_role_can_deduct_packaging($role)) {`

## material_inventory_role_can_set_packaging_on_hand {#material_inventory_role_can_set_packaging_on_hand}

**Callers (2):**

- `api/v1/materials.php`:323 — `'can_set_on_hand' => material_inventory_role_can_set_packaging_on_hand($role),`
- `includes/material_inventory.php`:53 — `return material_inventory_role_can_set_packaging_on_hand($role);`

## material_inventory_role_can_set_packaging {#material_inventory_role_can_set_packaging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_stock_in_supply {#material_inventory_role_can_stock_in_supply}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_request_supply {#material_inventory_role_can_request_supply}

**Callers (2):**

- `api/v1/materials.php`:167 — `if (!material_inventory_role_can_request_supply($role)) {`
- `api/v1/materials.php`:208 — `'can_submit_supply' => material_inventory_role_can_request_supply($role),`

## material_inventory_role_can_create_supply_catalog {#material_inventory_role_can_create_supply_catalog}

**Callers (1):**

- `api/v1/materials.php`:72 — `if (!material_inventory_role_can_create_supply_catalog($role)) {`

## material_supply_canonical_dept_id {#material_supply_canonical_dept_id}

**Tables:** `departments`

**Callers (1):**

- `includes/material_inventory.php`:224 — `$deptId = material_supply_canonical_dept_id($pdo);`

## material_inventory_has_columns {#material_inventory_has_columns}

**Callers (4):**

- `api/v1/costing.php`:55 — `$hasCategory = material_inventory_has_columns($pdo);`
- `includes/material_inventory.php`:167 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:212 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:1236 — `if (material_inventory_has_columns($pdo)) {`

## material_inventory_has_pool_columns {#material_inventory_has_pool_columns}

**Callers (3):**

- `includes/honey_inventory.php`:391 — `if (!material_inventory_has_pool_columns($pdo)) {`
- `includes/material_inventory.php`:179 — `$poolCols = material_inventory_has_pool_columns($pdo)`
- `includes/material_inventory.php`:444 — `if (!material_inventory_has_pool_columns($pdo)) {`

## material_inventory_enrich_packaging_row {#material_inventory_enrich_packaging_row}

**Callers (3):**

- `api/v1/materials.php`:314 — `$row = material_inventory_enrich_packaging_row($row);`
- `api/v1/materials.php`:401 — `'stock' => $row !== null ? material_inventory_enrich_packaging_row($row) : null,`
- `includes/material_inventory.php`:195 — `$row = material_inventory_enrich_packaging_row($row);`

## material_inventory_fetch_stock {#material_inventory_fetch_stock}

**Tables:** `stocks`, `departments`

**Callers (21):**

- `api/v1/materials.php`:154 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:310 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:398 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:420 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:449 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:507 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/dept_production.php`:265 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:253 — `$row = material_inventory_fetch_stock($pdo, $id);`
- `includes/material_inventory.php`:268 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:302 — `$updated = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:327 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:381 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:450 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:569 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:657 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:717 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:832 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:912 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:973 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:1026 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:1124 — `$stock = material_inventory_fetch_stock($pdo, $stockId, true);`

## material_inventory_list {#material_inventory_list}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:47 — `$items = material_inventory_list(`

## material_inventory_create {#material_inventory_create}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:80 — `$row = material_inventory_create($pdo, (int) $user['id'], $name, $deptId, $category, $unit);`

## material_inventory_update {#material_inventory_update}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:102 — `$row = material_inventory_update($pdo, $stockId, $name, $unit, $isActive);`

## material_inventory_adjust {#material_inventory_adjust}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:128 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);`
- `includes/material_inventory.php`:1147 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);`

## material_packaging_set_values {#material_packaging_set_values}

**Tables:** `stock_entries`, `stocks`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_set_pool_yield {#material_packaging_set_pool_yield}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:390 — `$result = material_packaging_set_pool_yield(`

## material_parse_lot_batch_label {#material_parse_lot_batch_label}

**Callers (3):**

- `includes/material_inventory.php`:503 — `return material_parse_lot_batch_label($label);`
- `includes/material_inventory.php`:522 — `$parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);`
- `includes/material_inventory.php`:783 — `$parsed = material_parse_lot_batch_label($label);`

## material_packaging_parse_lot_batch_label {#material_packaging_parse_lot_batch_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_pool_unit_cost_from_lots {#material_pool_unit_cost_from_lots}

**Tables:** `stock_entries`

**Callers (3):**

- `includes/material_inventory.php`:547 — `return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);`
- `includes/material_inventory.php`:619 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`
- `includes/material_inventory.php`:874 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`

## material_packaging_pool_unit_cost_from_lots {#material_packaging_pool_unit_cost_from_lots}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_stock_in_lot {#material_packaging_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:518 — `$result = material_packaging_stock_in_lot(`

## material_packaging_set_on_hand {#material_packaging_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:457 — `$result = material_packaging_set_on_hand(`
- `includes/material_inventory.php`:1133 — `$result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_packaging_deduct_for_use {#material_packaging_deduct_for_use}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:355 — `$result = material_packaging_deduct_for_use(`

## material_pool_recent_entries {#material_pool_recent_entries}

**Tables:** `stock_entries`, `users`

**Callers (2):**

- `includes/material_inventory.php`:804 — `return material_pool_recent_entries($pdo, $stockId, $limit);`
- `includes/material_inventory.php`:810 — `return material_pool_recent_entries($pdo, $stockId, $limit);`

## material_packaging_recent_entries {#material_packaging_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:316 — `$entries = material_packaging_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_recent_entries {#material_supply_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:426 — `$entries = material_supply_recent_entries($pdo, $stockId, 25);`

## material_supply_stock_in_lot {#material_supply_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:527 — `$result = material_supply_stock_in_lot(`

## material_supply_set_on_hand {#material_supply_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:465 — `$result = material_supply_set_on_hand(`
- `includes/material_inventory.php`:1145 — `$result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_supply_apply_issue {#material_supply_apply_issue}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:1136 — `$result = material_supply_apply_issue(`

## material_adjustment_submit {#material_adjustment_submit}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:180 — `$id = material_adjustment_submit(`

## material_adjustment_list {#material_adjustment_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:202 — `$requests = material_adjustment_list($pdo, $requestedBy);`

## material_adjustment_summary {#material_adjustment_summary}

**Callers (1):**

- `includes/material_inventory.php`:1083 — `$row['summary'] = material_adjustment_summary($row);`

## material_adjustment_approve {#material_adjustment_approve}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:222 — `material_adjustment_approve($pdo, $id, (int) $user['id']);`

## material_adjustment_reject {#material_adjustment_reject}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:243 — `material_adjustment_reject($pdo, $id, (int) $user['id'], $note);`

## material_adjustment_pending_count {#material_adjustment_pending_count}

**Tables:** `material_adjustment_requests`

**Callers (2):**

- `api/v1/honey.php`:343 — `$count += material_adjustment_pending_count($pdo);`
- `api/v1/materials.php`:259 — `api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);`

## material_adjustment_mark_viewed {#material_adjustment_mark_viewed}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:269 — `material_adjustment_mark_viewed(ims_pdo(), $id);`

## material_raw_snapshot {#material_raw_snapshot}

**Tables:** `honey_warehouse_rows`, `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:277 — `api_ok(material_raw_snapshot(ims_pdo()));`

## material_inventory_raw_stock_by_slug {#material_inventory_raw_stock_by_slug}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:285 — `$row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);`

