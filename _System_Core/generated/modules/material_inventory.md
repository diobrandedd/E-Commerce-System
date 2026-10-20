---
title: material_inventory
source: includes/material_inventory.php
generated: 2026-10-20T09:54:27+02:00
type: module
function_count: 86
---


# material_inventory

> Source: `includes/material_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/catalog_bootstrap.php`
- `includes/permissions.php`
- `includes/auth_check.php`
- `includes/inventory_edit_log.php`
- `includes/fulfillment_packing.php`
- `includes/fulfillment_packing.php`
- `includes/inventory_edit_log.php`
- `includes/honey_bottle_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/auth_check.php`

## material_inventory_role_can_view {#material_inventory_role_can_view}

**Callers (2):**

- `api/v1/materials.php`:48 — `if (!material_inventory_role_can_view($user)) {`
- `api/v1/materials.php`:61 — `if (!material_inventory_role_can_view($user)) {`

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

**Callers (2):**

- `api/v1/materials.php`:259 — `if (!material_inventory_role_can_request_packaging($user)) {`
- `api/v1/materials.php`:309 — `'can_submit_packaging' => material_inventory_role_can_request_packaging($user),`

## material_inventory_role_can_stock_in_packaging {#material_inventory_role_can_stock_in_packaging}

**Callers (3):**

- `api/v1/materials.php`:438 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($user),`
- `api/v1/materials.php`:809 — `if ($category === 'packaging' && !material_inventory_role_can_stock_in_packaging($user)) {`
- `api/v1/materials.php`:927 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($user),`

## material_inventory_role_can_deduct_packaging {#material_inventory_role_can_deduct_packaging}

**Callers (6):**

- `api/v1/materials.php`:440 — `'can_deduct' => material_inventory_role_can_deduct_packaging($user),`
- `api/v1/materials.php`:531 — `if (!material_inventory_role_can_deduct_packaging($user)) {`
- `api/v1/materials.php`:572 — `if (!material_inventory_role_can_deduct_packaging($user)) {`
- `api/v1/materials.php`:911 — `if (!ims_user_can($user, 'packaging.view') && !material_inventory_role_can_deduct_packaging($user)) {`
- `api/v1/materials.php`:926 — `'can_deduct' => material_inventory_role_can_deduct_packaging($user),`
- `includes/material_inventory.php`:2634 — `if (!material_inventory_role_can_deduct_packaging($user)) {`

## material_inventory_role_can_set_packaging_on_hand {#material_inventory_role_can_set_packaging_on_hand}

**Callers (2):**

- `api/v1/materials.php`:441 — `'can_set_on_hand' => material_inventory_role_can_set_packaging_on_hand($user),`
- `includes/material_inventory.php`:65 — `return material_inventory_role_can_set_packaging_on_hand($user);`

## material_inventory_role_can_set_packaging {#material_inventory_role_can_set_packaging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_stock_in_supply {#material_inventory_role_can_stock_in_supply}

**Callers (1):**

- `includes/material_inventory.php`:90 — `'can_stock_in' => material_inventory_role_can_stock_in_supply($user),`

## material_inventory_role_can_request_supply {#material_inventory_role_can_request_supply}

**Callers (2):**

- `api/v1/materials.php`:266 — `if (!material_inventory_role_can_request_supply($user)) {`
- `api/v1/materials.php`:310 — `'can_submit_supply' => material_inventory_role_can_request_supply($user),`

## material_inventory_role_can_issue_supply {#material_inventory_role_can_issue_supply}

**Callers (2):**

- `includes/material_inventory.php`:77 — `return material_inventory_role_can_issue_supply($user);`
- `includes/material_inventory.php`:91 — `'can_issue_request' => material_inventory_role_can_issue_supply($user),`

## material_supply_detail_permissions {#material_supply_detail_permissions}

**Callers (1):**

- `api/v1/materials.php`:699 — `'permissions' => material_supply_detail_permissions($user),`

## material_inventory_role_can_create_supply_catalog {#material_inventory_role_can_create_supply_catalog}

**Callers (2):**

- `api/v1/materials.php`:104 — `if (!material_inventory_role_can_create_supply_catalog($user)) {`
- `includes/material_inventory.php`:93 — `'can_create_catalog' => material_inventory_role_can_create_supply_catalog($user),`

## material_supply_canonical_dept_id {#material_supply_canonical_dept_id}

**Tables:** `departments`

**Callers (1):**

- `includes/material_inventory.php`:606 — `$deptId = material_supply_canonical_dept_id($pdo);`

## material_inventory_has_columns {#material_inventory_has_columns}

**Callers (4):**

- `api/v1/costing.php`:60 — `$hasCategory = material_inventory_has_columns($pdo);`
- `includes/material_inventory.php`:522 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:594 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:2454 — `if (material_inventory_has_columns($pdo)) {`

## material_inventory_has_pool_columns {#material_inventory_has_pool_columns}

**Callers (3):**

- `includes/fulfillment_packing.php`:713 — `$poolCol = material_inventory_has_pool_columns($pdo) ? ', s.pool_yield_qty, s.pool_yield_unit' : '';`
- `includes/material_inventory.php`:534 — `$poolCols = material_inventory_has_pool_columns($pdo)`
- `includes/material_inventory.php`:857 — `if (!material_inventory_has_pool_columns($pdo)) {`

## material_inventory_has_barcode_column {#material_inventory_has_barcode_column}

**Callers (5):**

- `api/v1/materials.php`:426 — `if (material_inventory_has_barcode_column($pdo)) {`
- `includes/material_inventory.php`:364 — `if ($stockId < 1 || !material_inventory_has_barcode_column($pdo)) {`
- `includes/material_inventory.php`:537 — `$barcodeCol = material_inventory_has_barcode_column($pdo) ? ', s.barcode' : '';`
- `includes/material_inventory.php`:570 — `&& material_inventory_has_barcode_column($pdo)`
- `includes/material_inventory.php`:2526 — `if (!material_inventory_has_barcode_column($pdo)) {`

## material_inventory_has_supply_category {#material_inventory_has_supply_category}

**Callers (8):**

- `includes/material_inventory.php`:198 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:215 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:244 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:272 — `if ($row['supply_category_name'] === null && $row['supply_category_id'] !== null && material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:326 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:538 — `$categoryCol = material_inventory_has_supply_category($pdo)`
- `includes/material_inventory.php`:542 — `$categoryJoin = material_inventory_has_supply_category($pdo)`
- `includes/material_inventory.php`:629 — `if ($category === 'packaging' && material_inventory_has_supply_category($pdo)) {`

## material_supply_categories_list {#material_supply_categories_list}

**Tables:** `packaging_supply_categories`

**Callers (1):**

- `api/v1/materials.php`:171 — `api_ok(['categories' => material_supply_categories_list(ims_pdo())]);`

## material_supply_category_create {#material_supply_category_create}

**Tables:** `packaging_supply_categories`

**Callers (1):**

- `api/v1/materials.php`:182 — `$row = material_supply_category_create(ims_pdo(), (int) $user['id'], $name);`

## material_supply_category_id_or_null {#material_supply_category_id_or_null}

**Tables:** `packaging_supply_categories`

**Callers (3):**

- `includes/material_inventory.php`:329 — `$categoryId = material_supply_category_id_or_null($pdo, $categoryId);`
- `includes/material_inventory.php`:611 — `? material_supply_category_id_or_null($pdo, $supplyCategoryId)`
- `includes/material_inventory.php`:701 — `$params[] = material_supply_category_id_or_null($pdo, $supplyCategoryId);`

## material_inventory_attach_supply_category {#material_inventory_attach_supply_category}

**Tables:** `packaging_supply_categories`

**Callers (1):**

- `includes/material_inventory.php`:516 — `return $row ? material_inventory_attach_supply_category($pdo, $row) : null;`

## material_inventory_has_supply_note {#material_inventory_has_supply_note}

**Callers (4):**

- `includes/material_inventory.php`:541 — `$noteCol = material_inventory_has_supply_note($pdo) ? ', s.supply_note' : '';`
- `includes/material_inventory.php`:613 — `$supplyNote = material_inventory_has_supply_note($pdo)`
- `includes/material_inventory.php`:633 — `if (material_inventory_has_supply_note($pdo)) {`
- `includes/material_inventory.php`:704 — `if (!material_inventory_has_supply_note($pdo)) {`

## material_supply_note_or_null {#material_supply_note_or_null}

**Callers (2):**

- `includes/material_inventory.php`:614 — `? material_supply_note_or_null($supplyNote)`
- `includes/material_inventory.php`:708 — `$params[] = material_supply_note_or_null($supplyNote);`

## material_supply_category_assign_many {#material_supply_category_assign_many}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:201 — `$updated = material_supply_category_assign_many(ims_pdo(), $categoryId, $stockIds);`

## material_packaging_barcode_for_id {#material_packaging_barcode_for_id}

**Callers (5):**

- `includes/material_inventory.php`:365 — `return material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:371 — `return material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:378 — `return material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:380 — `$code = material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:482 — `$barcode = material_packaging_barcode_for_id((int) $row['id']);`

## material_packaging_ensure_barcode {#material_packaging_ensure_barcode}

**Tables:** `stocks`

**Callers (5):**

- `api/v1/materials.php`:427 — `$row['barcode'] = material_packaging_ensure_barcode($pdo, $stockId);`
- `includes/material_inventory.php`:573 — `$row['barcode'] = material_packaging_ensure_barcode($pdo, $sid);`
- `includes/material_inventory.php`:646 — `material_packaging_ensure_barcode($pdo, $id);`
- `includes/material_inventory.php`:2553 — `$row['barcode'] = material_packaging_ensure_barcode($pdo, $id);`
- `includes/material_inventory.php`:2591 — `$code = material_packaging_ensure_barcode($pdo, $id);`

## material_packaging_last_lot_qty {#material_packaging_last_lot_qty}

**Tables:** `stock_entries`

**Callers (3):**

- `api/v1/materials.php`:429 — `$lot = material_packaging_last_lot_qty($pdo, $stockId);`
- `includes/material_inventory.php`:2531 — `$lot = material_packaging_last_lot_qty($pdo, $id);`
- `includes/material_inventory.php`:2555 — `$lot = material_packaging_last_lot_qty($pdo, $id);`

## material_packaging_last_lot_qty_map {#material_packaging_last_lot_qty_map}

**Tables:** `stock_entries`

**Callers (1):**

- `includes/material_inventory.php`:564 — `$lotMap = material_packaging_last_lot_qty_map($pdo, $packagingIds);`

## material_inventory_enrich_packaging_row {#material_inventory_enrich_packaging_row}

**Callers (5):**

- `api/v1/materials.php`:430 — `$row = material_inventory_enrich_packaging_row($row, $lot);`
- `api/v1/materials.php`:668 — `'stock' => $row !== null ? material_inventory_enrich_packaging_row($row) : null,`
- `includes/material_inventory.php`:575 — `$row = material_inventory_enrich_packaging_row($row, $lotQty);`
- `includes/material_inventory.php`:2533 — `return material_inventory_enrich_packaging_row($row, $lot);`
- `includes/material_inventory.php`:2557 — `return material_inventory_enrich_packaging_row($row, $lot);`

## material_inventory_fetch_stock {#material_inventory_fetch_stock}

**Tables:** `stocks`, `departments`

**Callers (35):**

- `api/v1/materials.php`:253 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:422 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:500 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:648 — `$stockRow = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:665 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:688 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:725 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:801 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/dept_production.php`:318 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:279 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/fulfillment_packing.php`:355 — `$stockRow = material_inventory_fetch_stock($pdo, $sid);`
- `includes/fulfillment_packing.php`:444 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:468 — `$stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);`
- `includes/fulfillment_packing.php`:595 — `$stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);`
- `includes/fulfillment_packing.php`:645 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:777 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:1029 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:648 — `$row = material_inventory_fetch_stock($pdo, $id);`
- `includes/material_inventory.php`:667 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:715 — `$updated = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:740 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:794 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:863 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:1180 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:1291 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- _… and 10 more (run impact.php for full list)_

## material_inventory_list {#material_inventory_list}

**Tables:** `stock_entries`, `stocks`, `packaging_supply_categories`, `departments`

**Callers (2):**

- `api/v1/materials.php`:68 — `$items = material_inventory_list(`
- `includes/material_inventory.php`:2581 — `$items = material_inventory_list($pdo, 'packaging', true);`

## material_inventory_create {#material_inventory_create}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:112 — `$row = material_inventory_create(`

## material_inventory_update {#material_inventory_update}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:150 — `$row = material_inventory_update(`
- `includes/material_inventory.php`:1455 — `$updated = material_inventory_update($pdo, $stockId, null, null, false);`

## material_inventory_adjust {#material_inventory_adjust}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:227 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);`
- `includes/material_inventory.php`:2336 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);`

## material_packaging_set_values {#material_packaging_set_values}

**Tables:** `stock_entries`, `stocks`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_set_pool_yield {#material_packaging_set_pool_yield}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:657 — `$result = material_packaging_set_pool_yield(`
- `includes/material_inventory.php`:1052 — `material_packaging_set_pool_yield($pdo, $stockId, $yield, 'foam_piece', $userId);`

## material_parse_lot_batch_label {#material_parse_lot_batch_label}

**Callers (3):**

- `includes/material_inventory.php`:916 — `return material_parse_lot_batch_label($label);`
- `includes/material_inventory.php`:935 — `$parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);`
- `includes/material_inventory.php`:1793 — `$parsed = material_parse_lot_batch_label($label);`

## material_packaging_parse_lot_batch_label {#material_packaging_parse_lot_batch_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_pool_unit_cost_from_lots {#material_pool_unit_cost_from_lots}

**Tables:** `stock_entries`

**Callers (4):**

- `includes/fulfillment_packing.php`:1068 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/material_inventory.php`:960 — `return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);`
- `includes/material_inventory.php`:1253 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`
- `includes/material_inventory.php`:2036 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`

## material_packaging_pool_unit_cost_from_lots {#material_packaging_pool_unit_cost_from_lots}

**Callers (3):**

- `includes/material_inventory.php`:1124 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/material_inventory.php`:1151 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/parcel_pack_costing.php`:453 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`

## material_packaging_is_bubble_sized_stock_name {#material_packaging_is_bubble_sized_stock_name}

**Callers (1):**

- `includes/parcel_pack_costing.php`:446 — `if ($lineUnit === 'bubble_piece' && material_packaging_is_bubble_sized_stock_name($stockName)) {`

## material_packaging_is_foam_sized_stock_name {#material_packaging_is_foam_sized_stock_name}

**Callers (1):**

- `includes/parcel_pack_costing.php`:449 — `if ($lineUnit === 'foam_piece' && material_packaging_is_foam_sized_stock_name($stockName)) {`

## material_packaging_generic_foam_roll_stock_name {#material_packaging_generic_foam_roll_stock_name}

**Callers (1):**

- `includes/material_inventory.php`:980 — `return strtolower(trim($name)) === strtolower(material_packaging_generic_foam_roll_stock_name());`

## material_packaging_is_generic_foam_roll_name {#material_packaging_is_generic_foam_roll_name}

**Callers (3):**

- `api/v1/materials.php`:433 — `$isGenericFoam = material_packaging_is_generic_foam_roll_name((string) $row['name']);`
- `api/v1/materials.php`:501 — `if ($row === null || !material_packaging_is_generic_foam_roll_name((string) $row['name'])) {`
- `api/v1/materials.php`:652 — `if (material_packaging_is_generic_foam_roll_name((string) $stockRow['name'])) {`

## material_packaging_foam_cacao_cut_stock_names {#material_packaging_foam_cacao_cut_stock_names}

**Callers (2):**

- `includes/material_inventory.php`:1002 — `foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {`
- `includes/material_inventory.php`:1041 — `foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {`

## material_packaging_foam_cacao_cut_yields {#material_packaging_foam_cacao_cut_yields}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:449 — `$payload['foam_cacao_cut_yields'] = material_packaging_foam_cacao_cut_yields($pdo);`
- `api/v1/materials.php`:521 — `'foam_cacao_cut_yields' => material_packaging_foam_cacao_cut_yields($pdo),`

## material_packaging_set_foam_cacao_cut_yields {#material_packaging_set_foam_cacao_cut_yields}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:506 — `$result = material_packaging_set_foam_cacao_cut_yields(`

## material_inventory_is_fulfillment_parcel_stock_name {#material_inventory_is_fulfillment_parcel_stock_name}

**Callers (1):**

- `includes/material_inventory.php`:1099 — `if ($cat === 'packaging' && material_inventory_is_fulfillment_parcel_stock_name((string) ($row['name'] ?? ''))) {`

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

- `api/v1/materials.php`:818 — `$result = material_packaging_stock_in_lot(`

## material_packaging_set_on_hand {#material_packaging_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:733 — `$result = material_packaging_set_on_hand(`
- `includes/material_inventory.php`:2322 — `$result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_packaging_deduct_for_use {#material_packaging_deduct_for_use}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:549 — `$result = material_packaging_deduct_for_use(`

## material_packaging_verify_remove_password {#material_packaging_verify_remove_password}

**Tables:** `users`

**Callers (1):**

- `includes/material_inventory.php`:1442 — `if (!material_packaging_verify_remove_password($pdo, $confirmPassword)) {`

## material_packaging_catalog_remove {#material_packaging_catalog_remove}

**Callers (1):**

- `api/v1/materials.php`:471 — `$result = material_packaging_catalog_remove($pdo, $stockId, $password);`

## material_packaging_report_damage {#material_packaging_report_damage}

**Tables:** `stock_entries`, `packaging_material_damage_log`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:590 — `$result = material_packaging_report_damage(`

## material_packaging_damage_totals {#material_packaging_damage_totals}

**Tables:** `packaging_material_damage_log`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:617 — `$totals = material_packaging_damage_totals(ims_pdo(), $from, $to);`

## material_fulfillment_snap_sixteenths {#material_fulfillment_snap_sixteenths}

**Callers (6):**

- `includes/fulfillment_packing.php`:116 — `return material_fulfillment_snap_sixteenths($pieces / $yield);`
- `includes/fulfillment_packing.php`:661 — `$deductQty = material_fulfillment_snap_sixteenths($qty / $yield);`
- `includes/fulfillment_packing.php`:663 — `$deductQty = material_fulfillment_snap_sixteenths($qty);`
- `includes/fulfillment_packing.php`:795 — `$rollQty = material_fulfillment_snap_sixteenths($qty);`
- `includes/material_inventory.php`:1621 — `$deductQty = material_fulfillment_snap_sixteenths($qty / $yield);`
- `includes/material_inventory.php`:1623 — `$deductQty = material_fulfillment_snap_sixteenths($qty);`

## material_packaging_deduct_fulfillment_line {#material_packaging_deduct_fulfillment_line}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:1707 — `$legacy = material_packaging_deduct_fulfillment_line(`

## parcel_fulfillment_already_deducted {#parcel_fulfillment_already_deducted}

**Tables:** `online_parcel_fulfillment_deduct_lines`

**Callers (1):**

- `includes/material_inventory.php`:1683 — `if (parcel_fulfillment_already_deducted($pdo, $parcelId)) {`

## parcel_fulfillment_apply_deduct {#parcel_fulfillment_apply_deduct}

**Tables:** `online_parcel_fulfillment_deduct_lines`

**Callers (1):**

- `includes/honey_parcel_inventory.php`:97 — `$fulfillmentDeducted = parcel_fulfillment_apply_deduct($pdo, $parcelId, $userId, $itemsRaw);`

## parcel_fulfillment_revert_deduct {#parcel_fulfillment_revert_deduct}

**Tables:** `online_parcel_fulfillment_deduct_lines`, `fulfillment_packing_balances`, `stocks`

**Callers (1):**

- `includes/honey_parcel_inventory.php`:167 — `parcel_fulfillment_revert_deduct($pdo, $parcelId);`

## material_pool_recent_entries {#material_pool_recent_entries}

**Tables:** `stock_entries`, `users`

**Callers (2):**

- `includes/material_inventory.php`:1816 — `return material_pool_recent_entries($pdo, $stockId, $limit);`
- `includes/material_inventory.php`:1967 — `return material_pool_recent_entries($pdo, $stockId, $limit);`

## material_packaging_recent_entries {#material_packaging_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:432 — `$entries = material_packaging_recent_entries($pdo, $stockId, $logLimit);`

## material_packaging_stock_out_history {#material_packaging_stock_out_history}

**Tables:** `stock_entries`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:958 — `api_ok(material_packaging_stock_out_history(ims_pdo(), $filters, $page, $perPage));`

## material_supply_recent_entries {#material_supply_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:695 — `$entries = material_supply_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_stock_in_lot {#material_supply_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:828 — `$result = material_supply_stock_in_lot(`

## material_supply_set_on_hand {#material_supply_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:741 — `$result = material_supply_set_on_hand(`
- `includes/material_inventory.php`:2334 — `$result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_supply_apply_issue {#material_supply_apply_issue}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:2325 — `$result = material_supply_apply_issue(`

## material_adjustment_submit {#material_adjustment_submit}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:279 — `$id = material_adjustment_submit(`

## material_adjustment_list {#material_adjustment_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:304 — `$requests = material_adjustment_list($pdo, $requestedBy, $category ?: null, $requestAction ?: null);`

## material_adjustment_summary {#material_adjustment_summary}

**Callers (2):**

- `includes/material_inventory.php`:2272 — `$row['summary'] = material_adjustment_summary($row);`
- `includes/material_inventory.php`:2418 — `$row['summary'] = material_adjustment_summary($row);`

## material_adjustment_approve {#material_adjustment_approve}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:324 — `material_adjustment_approve($pdo, $id, (int) $user['id']);`

## material_adjustment_reject {#material_adjustment_reject}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:345 — `material_adjustment_reject($pdo, $id, (int) $user['id'], $note);`

## material_adjustment_pending_count {#material_adjustment_pending_count}

**Tables:** `material_adjustment_requests`

**Callers (2):**

- `api/v1/honey.php`:777 — `$count += material_adjustment_pending_count($pdo);`
- `api/v1/materials.php`:361 — `api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);`

## material_adjustment_mark_viewed {#material_adjustment_mark_viewed}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:371 — `material_adjustment_mark_viewed(ims_pdo(), $id);`

## material_adjustment_unread_list {#material_adjustment_unread_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:381 — `$requests = material_adjustment_unread_list(ims_pdo(), $limit);`

## material_raw_snapshot {#material_raw_snapshot}

**Tables:** `honey_warehouse_rows`, `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:389 — `api_ok(material_raw_snapshot(ims_pdo()));`

## material_inventory_raw_stock_by_slug {#material_inventory_raw_stock_by_slug}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:397 — `$row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);`

## material_packaging_lookup_by_barcode {#material_packaging_lookup_by_barcode}

**Tables:** `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:919 — `$row = material_packaging_lookup_by_barcode(ims_pdo(), $code);`

## material_packaging_label_cell_html {#material_packaging_label_cell_html}

**Callers (1):**

- `includes/material_inventory.php`:2595 — `'html' => material_packaging_label_cell_html($code, $name),`

## material_packaging_print_sheet_html {#material_packaging_print_sheet_html}

**Callers (1):**

- `api/v1/materials.php`:900 — `$html = material_packaging_print_sheet_html(ims_pdo());`

## material_packaging_access_roster {#material_packaging_access_roster}

**Tables:** `users`, `roles`

**Callers (1):**

- `api/v1/materials.php`:939 — `api_ok(material_packaging_access_roster(ims_pdo()));`

