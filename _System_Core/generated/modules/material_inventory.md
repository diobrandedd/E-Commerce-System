---
title: material_inventory
source: includes/material_inventory.php
generated: 2026-06-24T03:46:06+02:00
type: module
function_count: 64
---


# material_inventory

> Source: `includes/material_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/catalog_bootstrap.php`
- `includes/fulfillment_packing.php`
- `includes/fulfillment_packing.php`

## material_inventory_role_can_view {#material_inventory_role_can_view}

**Callers (2):**

- `api/v1/materials.php`:38 — `if (!material_inventory_role_can_view((string) $user['role'])) {`
- `api/v1/materials.php`:51 — `if (!material_inventory_role_can_view($role)) {`

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

- `api/v1/materials.php`:227 — `'can_submit_packaging' => material_inventory_role_can_request_packaging($role),`

## material_inventory_role_can_stock_in_packaging {#material_inventory_role_can_stock_in_packaging}

**Callers (2):**

- `api/v1/materials.php`:352 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($role),`
- `api/v1/materials.php`:615 — `if ($category === 'packaging' && !material_inventory_role_can_stock_in_packaging($role)) {`

## material_inventory_role_can_deduct_packaging {#material_inventory_role_can_deduct_packaging}

**Callers (2):**

- `api/v1/materials.php`:354 — `'can_deduct' => material_inventory_role_can_deduct_packaging($role),`
- `api/v1/materials.php`:415 — `if (!material_inventory_role_can_deduct_packaging($role)) {`

## material_inventory_role_can_set_packaging_on_hand {#material_inventory_role_can_set_packaging_on_hand}

**Callers (2):**

- `api/v1/materials.php`:355 — `'can_set_on_hand' => material_inventory_role_can_set_packaging_on_hand($role),`
- `includes/material_inventory.php`:53 — `return material_inventory_role_can_set_packaging_on_hand($role);`

## material_inventory_role_can_set_packaging {#material_inventory_role_can_set_packaging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_stock_in_supply {#material_inventory_role_can_stock_in_supply}

**Callers (1):**

- `includes/material_inventory.php`:75 — `'can_stock_in' => material_inventory_role_can_stock_in_supply($role),`

## material_inventory_role_can_request_supply {#material_inventory_role_can_request_supply}

**Callers (2):**

- `api/v1/materials.php`:184 — `if (!material_inventory_role_can_request_supply($role)) {`
- `api/v1/materials.php`:228 — `'can_submit_supply' => material_inventory_role_can_request_supply($role),`

## material_inventory_role_can_issue_supply {#material_inventory_role_can_issue_supply}

**Callers (2):**

- `includes/material_inventory.php`:63 — `return material_inventory_role_can_issue_supply($role);`
- `includes/material_inventory.php`:76 — `'can_issue_request' => material_inventory_role_can_issue_supply($role),`

## material_supply_detail_permissions {#material_supply_detail_permissions}

**Callers (1):**

- `api/v1/materials.php`:517 — `'permissions' => material_supply_detail_permissions($role),`

## material_inventory_role_can_create_supply_catalog {#material_inventory_role_can_create_supply_catalog}

**Callers (2):**

- `api/v1/materials.php`:89 — `if (!material_inventory_role_can_create_supply_catalog($role)) {`
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
- `includes/material_inventory.php`:1724 — `if (material_inventory_has_columns($pdo)) {`

## material_inventory_has_pool_columns {#material_inventory_has_pool_columns}

**Callers (4):**

- `includes/fulfillment_packing.php`:712 — `$poolCol = material_inventory_has_pool_columns($pdo) ? ', s.pool_yield_qty, s.pool_yield_unit' : '';`
- `includes/honey_inventory.php`:394 — `if (!material_inventory_has_pool_columns($pdo)) {`
- `includes/material_inventory.php`:196 — `$poolCols = material_inventory_has_pool_columns($pdo)`
- `includes/material_inventory.php`:461 — `if (!material_inventory_has_pool_columns($pdo)) {`

## material_inventory_enrich_packaging_row {#material_inventory_enrich_packaging_row}

**Callers (3):**

- `api/v1/materials.php`:344 — `$row = material_inventory_enrich_packaging_row($row);`
- `api/v1/materials.php`:486 — `'stock' => $row !== null ? material_inventory_enrich_packaging_row($row) : null,`
- `includes/material_inventory.php`:212 — `$row = material_inventory_enrich_packaging_row($row);`

## material_inventory_fetch_stock {#material_inventory_fetch_stock}

**Tables:** `stocks`, `departments`

**Callers (32):**

- `api/v1/materials.php`:171 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:340 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:383 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:466 — `$stockRow = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:483 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:506 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:537 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:607 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/dept_production.php`:318 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:278 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/fulfillment_packing.php`:354 — `$stockRow = material_inventory_fetch_stock($pdo, $sid);`
- `includes/fulfillment_packing.php`:443 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:467 — `$stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);`
- `includes/fulfillment_packing.php`:594 — `$stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);`
- `includes/fulfillment_packing.php`:644 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:776 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:1028 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:270 — `$row = material_inventory_fetch_stock($pdo, $id);`
- `includes/material_inventory.php`:285 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:319 — `$updated = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:344 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:398 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:467 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:784 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:895 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- _… and 7 more (run impact.php for full list)_

## material_inventory_list {#material_inventory_list}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:58 — `$items = material_inventory_list(`

## material_inventory_create {#material_inventory_create}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:97 — `$row = material_inventory_create($pdo, (int) $user['id'], $name, $deptId, $category, $unit);`

## material_inventory_update {#material_inventory_update}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:119 — `$row = material_inventory_update($pdo, $stockId, $name, $unit, $isActive);`

## material_inventory_adjust {#material_inventory_adjust}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:145 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);`
- `includes/material_inventory.php`:1606 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);`

## material_packaging_set_values {#material_packaging_set_values}

**Tables:** `stock_entries`, `stocks`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_set_pool_yield {#material_packaging_set_pool_yield}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:475 — `$result = material_packaging_set_pool_yield(`
- `includes/material_inventory.php`:656 — `material_packaging_set_pool_yield($pdo, $stockId, $yield, 'foam_piece', $userId);`

## material_parse_lot_batch_label {#material_parse_lot_batch_label}

**Callers (3):**

- `includes/material_inventory.php`:520 — `return material_parse_lot_batch_label($label);`
- `includes/material_inventory.php`:539 — `$parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);`
- `includes/material_inventory.php`:1221 — `$parsed = material_parse_lot_batch_label($label);`

## material_packaging_parse_lot_batch_label {#material_packaging_parse_lot_batch_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_pool_unit_cost_from_lots {#material_pool_unit_cost_from_lots}

**Tables:** `stock_entries`

**Callers (4):**

- `includes/fulfillment_packing.php`:1067 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/material_inventory.php`:564 — `return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);`
- `includes/material_inventory.php`:857 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`
- `includes/material_inventory.php`:1317 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`

## material_packaging_pool_unit_cost_from_lots {#material_packaging_pool_unit_cost_from_lots}

**Callers (3):**

- `includes/material_inventory.php`:728 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/material_inventory.php`:755 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/parcel_pack_costing.php`:453 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`

## material_packaging_is_bubble_sized_stock_name {#material_packaging_is_bubble_sized_stock_name}

**Callers (1):**

- `includes/parcel_pack_costing.php`:446 — `if ($lineUnit === 'bubble_piece' && material_packaging_is_bubble_sized_stock_name($stockName)) {`

## material_packaging_is_foam_sized_stock_name {#material_packaging_is_foam_sized_stock_name}

**Callers (1):**

- `includes/parcel_pack_costing.php`:449 — `if ($lineUnit === 'foam_piece' && material_packaging_is_foam_sized_stock_name($stockName)) {`

## material_packaging_generic_foam_roll_stock_name {#material_packaging_generic_foam_roll_stock_name}

**Callers (1):**

- `includes/material_inventory.php`:584 — `return strtolower(trim($name)) === strtolower(material_packaging_generic_foam_roll_stock_name());`

## material_packaging_is_generic_foam_roll_name {#material_packaging_is_generic_foam_roll_name}

**Callers (3):**

- `api/v1/materials.php`:347 — `$isGenericFoam = material_packaging_is_generic_foam_roll_name((string) $row['name']);`
- `api/v1/materials.php`:384 — `if ($row === null || !material_packaging_is_generic_foam_roll_name((string) $row['name'])) {`
- `api/v1/materials.php`:470 — `if (material_packaging_is_generic_foam_roll_name((string) $stockRow['name'])) {`

## material_packaging_foam_cacao_cut_stock_names {#material_packaging_foam_cacao_cut_stock_names}

**Callers (2):**

- `includes/material_inventory.php`:606 — `foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {`
- `includes/material_inventory.php`:645 — `foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {`

## material_packaging_foam_cacao_cut_yields {#material_packaging_foam_cacao_cut_yields}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:362 — `$payload['foam_cacao_cut_yields'] = material_packaging_foam_cacao_cut_yields($pdo);`
- `api/v1/materials.php`:404 — `'foam_cacao_cut_yields' => material_packaging_foam_cacao_cut_yields($pdo),`

## material_packaging_set_foam_cacao_cut_yields {#material_packaging_set_foam_cacao_cut_yields}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:389 — `$result = material_packaging_set_foam_cacao_cut_yields(`

## material_inventory_is_fulfillment_parcel_stock_name {#material_inventory_is_fulfillment_parcel_stock_name}

**Callers (1):**

- `includes/material_inventory.php`:703 — `if ($cat === 'packaging' && material_inventory_is_fulfillment_parcel_stock_name((string) ($row['name'] ?? ''))) {`

## material_inventory_eligible_for_product_formula_row {#material_inventory_eligible_for_product_formula_row}

**Callers (3):**

- `api/v1/costing.php`:91 — `if (!material_inventory_eligible_for_product_formula_row($row)) {`
- `api/v1/costing.php`:401 — `if (!material_inventory_eligible_for_product_formula_row($stock)) {`
- `api/v1/costing.php`:437 — `if (!material_inventory_eligible_for_product_formula_row($stock)) {`

## material_packaging_generic_bubble_roll_unit_cost {#material_packaging_generic_bubble_roll_unit_cost}

**Tables:** `stocks`

**Callers (1):**

- `includes/parcel_pack_costing.php`:447 — `$rollCost = material_packaging_generic_bubble_roll_unit_cost($pdo);`

## material_packaging_generic_foam_roll_unit_cost {#material_packaging_generic_foam_roll_unit_cost}

**Tables:** `stocks`

**Callers (1):**

- `includes/parcel_pack_costing.php`:450 — `$rollCost = material_packaging_generic_foam_roll_unit_cost($pdo);`

## material_packaging_stock_in_lot {#material_packaging_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:624 — `$result = material_packaging_stock_in_lot(`

## material_packaging_set_on_hand {#material_packaging_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:545 — `$result = material_packaging_set_on_hand(`
- `includes/material_inventory.php`:1592 — `$result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_packaging_deduct_for_use {#material_packaging_deduct_for_use}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:433 — `$result = material_packaging_deduct_for_use(`

## material_fulfillment_snap_sixteenths {#material_fulfillment_snap_sixteenths}

**Callers (6):**

- `includes/fulfillment_packing.php`:115 — `return material_fulfillment_snap_sixteenths($pieces / $yield);`
- `includes/fulfillment_packing.php`:660 — `$deductQty = material_fulfillment_snap_sixteenths($qty / $yield);`
- `includes/fulfillment_packing.php`:662 — `$deductQty = material_fulfillment_snap_sixteenths($qty);`
- `includes/fulfillment_packing.php`:794 — `$rollQty = material_fulfillment_snap_sixteenths($qty);`
- `includes/material_inventory.php`:1049 — `$deductQty = material_fulfillment_snap_sixteenths($qty / $yield);`
- `includes/material_inventory.php`:1051 — `$deductQty = material_fulfillment_snap_sixteenths($qty);`

## material_packaging_deduct_fulfillment_line {#material_packaging_deduct_fulfillment_line}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:1135 — `$legacy = material_packaging_deduct_fulfillment_line(`

## parcel_fulfillment_already_deducted {#parcel_fulfillment_already_deducted}

**Tables:** `online_parcel_fulfillment_deduct_lines`

**Callers (2):**

- `includes/material_inventory.php`:1111 — `if (parcel_fulfillment_already_deducted($pdo, $parcelId)) {`
- `includes/online_order_parcels.php`:606 — `|| parcel_fulfillment_already_deducted($pdo, $parcelId);`

## parcel_fulfillment_apply_deduct {#parcel_fulfillment_apply_deduct}

**Tables:** `online_parcel_fulfillment_deduct_lines`

**Callers (1):**

- `includes/honey_parcel_inventory.php`:90 — `$fulfillmentDeducted = parcel_fulfillment_apply_deduct($pdo, $parcelId, $userId, $itemsRaw);`

## parcel_fulfillment_revert_deduct {#parcel_fulfillment_revert_deduct}

**Tables:** `online_parcel_fulfillment_deduct_lines`, `fulfillment_packing_balances`, `stocks`

**Callers (1):**

- `includes/honey_parcel_inventory.php`:160 — `parcel_fulfillment_revert_deduct($pdo, $parcelId);`

## material_pool_recent_entries {#material_pool_recent_entries}

**Tables:** `stock_entries`, `users`

**Callers (2):**

- `includes/material_inventory.php`:1242 — `return material_pool_recent_entries($pdo, $stockId, $limit);`
- `includes/material_inventory.php`:1248 — `return material_pool_recent_entries($pdo, $stockId, $limit);`

## material_packaging_recent_entries {#material_packaging_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:346 — `$entries = material_packaging_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_recent_entries {#material_supply_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:513 — `$entries = material_supply_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_stock_in_lot {#material_supply_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:634 — `$result = material_supply_stock_in_lot(`

## material_supply_set_on_hand {#material_supply_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:553 — `$result = material_supply_set_on_hand(`
- `includes/material_inventory.php`:1604 — `$result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_supply_apply_issue {#material_supply_apply_issue}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:1595 — `$result = material_supply_apply_issue(`

## material_adjustment_submit {#material_adjustment_submit}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:197 — `$id = material_adjustment_submit(`

## material_adjustment_list {#material_adjustment_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:222 — `$requests = material_adjustment_list($pdo, $requestedBy, $category ?: null, $requestAction ?: null);`

## material_adjustment_summary {#material_adjustment_summary}

**Callers (2):**

- `includes/material_inventory.php`:1542 — `$row['summary'] = material_adjustment_summary($row);`
- `includes/material_inventory.php`:1688 — `$row['summary'] = material_adjustment_summary($row);`

## material_adjustment_approve {#material_adjustment_approve}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:242 — `material_adjustment_approve($pdo, $id, (int) $user['id']);`

## material_adjustment_reject {#material_adjustment_reject}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:263 — `material_adjustment_reject($pdo, $id, (int) $user['id'], $note);`

## material_adjustment_pending_count {#material_adjustment_pending_count}

**Tables:** `material_adjustment_requests`

**Callers (2):**

- `api/v1/honey.php`:424 — `$count += material_adjustment_pending_count($pdo);`
- `api/v1/materials.php`:279 — `api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);`

## material_adjustment_mark_viewed {#material_adjustment_mark_viewed}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:289 — `material_adjustment_mark_viewed(ims_pdo(), $id);`

## material_adjustment_unread_list {#material_adjustment_unread_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:299 — `$requests = material_adjustment_unread_list(ims_pdo(), $limit);`

## material_raw_snapshot {#material_raw_snapshot}

**Tables:** `honey_warehouse_rows`, `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:307 — `api_ok(material_raw_snapshot(ims_pdo()));`

## material_inventory_raw_stock_by_slug {#material_inventory_raw_stock_by_slug}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:315 — `$row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);`

