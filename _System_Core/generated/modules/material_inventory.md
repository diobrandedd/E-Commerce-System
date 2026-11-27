---
title: material_inventory
source: includes/material_inventory.php
generated: 2026-11-27T06:41:00+01:00
type: module
function_count: 90
---


# material_inventory

> Source: `includes/material_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/catalog_bootstrap.php`
- `includes/permissions.php`
- `includes/inventory_edit_log.php`
- `includes/fulfillment_packing.php`
- `includes/fulfillment_packing.php`
- `includes/inventory_edit_log.php`
- `includes/honey_bottle_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/auth_check.php`
- `includes/auth_check.php`
- `includes/permissions.php`
- `includes/auth_check.php`
- `includes/permissions.php`
- `includes/permissions.php`

## material_inventory_role_can_view {#material_inventory_role_can_view}

**Callers (2):**

- `api/v1/materials.php`:51 — `if (!material_inventory_role_can_view($user)) {`
- `api/v1/materials.php`:64 — `if (!material_inventory_role_can_view($user)) {`

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

- `api/v1/materials.php`:262 — `if (!material_inventory_role_can_request_packaging($user)) {`
- `api/v1/materials.php`:312 — `'can_submit_packaging' => material_inventory_role_can_request_packaging($user),`

## material_inventory_role_can_stock_in_packaging {#material_inventory_role_can_stock_in_packaging}

**Callers (3):**

- `api/v1/materials.php`:441 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($user),`
- `api/v1/materials.php`:800 — `if ($category === 'packaging' && !material_inventory_role_can_stock_in_packaging($user)) {`
- `api/v1/materials.php`:949 — `'can_stock_in' => material_inventory_role_can_stock_in_packaging($user),`

## material_inventory_role_can_deduct_packaging {#material_inventory_role_can_deduct_packaging}

**Callers (7):**

- `api/v1/materials.php`:443 — `'can_deduct' => material_inventory_role_can_deduct_packaging($user),`
- `api/v1/materials.php`:534 — `if (!material_inventory_role_can_deduct_packaging($user)) {`
- `api/v1/materials.php`:575 — `if (!material_inventory_role_can_deduct_packaging($user)) {`
- `api/v1/materials.php`:933 — `if (!ims_user_can($user, 'packaging.view') && !material_inventory_role_can_deduct_packaging($user)) {`
- `api/v1/materials.php`:948 — `'can_deduct' => material_inventory_role_can_deduct_packaging($user),`
- `includes/material_inventory.php`:2660 — `if (!material_inventory_role_can_deduct_packaging($user)) {`
- `includes/material_inventory.php`:2706 — `if (material_inventory_role_can_deduct_packaging($user)) {`

## material_inventory_role_can_set_packaging_on_hand {#material_inventory_role_can_set_packaging_on_hand}

**Callers (2):**

- `api/v1/materials.php`:444 — `'can_set_on_hand' => material_inventory_role_can_set_packaging_on_hand($user),`
- `includes/material_inventory.php`:66 — `return material_inventory_role_can_set_packaging_on_hand($user);`

## material_inventory_role_can_set_packaging {#material_inventory_role_can_set_packaging}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_inventory_role_can_stock_in_supply {#material_inventory_role_can_stock_in_supply}

**Callers (1):**

- `includes/material_inventory.php`:91 — `'can_stock_in' => material_inventory_role_can_stock_in_supply($user),`

## material_inventory_role_can_request_supply {#material_inventory_role_can_request_supply}

**Callers (2):**

- `api/v1/materials.php`:269 — `if (!material_inventory_role_can_request_supply($user)) {`
- `api/v1/materials.php`:313 — `'can_submit_supply' => material_inventory_role_can_request_supply($user),`

## material_inventory_role_can_issue_supply {#material_inventory_role_can_issue_supply}

**Callers (2):**

- `includes/material_inventory.php`:78 — `return material_inventory_role_can_issue_supply($user);`
- `includes/material_inventory.php`:92 — `'can_issue_request' => material_inventory_role_can_issue_supply($user),`

## material_supply_detail_permissions {#material_supply_detail_permissions}

**Callers (1):**

- `api/v1/materials.php`:702 — `'permissions' => material_supply_detail_permissions($user),`

## material_inventory_role_can_create_supply_catalog {#material_inventory_role_can_create_supply_catalog}

**Callers (2):**

- `api/v1/materials.php`:107 — `if (!material_inventory_role_can_create_supply_catalog($user)) {`
- `includes/material_inventory.php`:94 — `'can_create_catalog' => material_inventory_role_can_create_supply_catalog($user),`

## material_supply_canonical_dept_id {#material_supply_canonical_dept_id}

**Tables:** `departments`

**Callers (1):**

- `includes/material_inventory.php`:607 — `$deptId = material_supply_canonical_dept_id($pdo);`

## material_inventory_has_columns {#material_inventory_has_columns}

**Callers (4):**

- `api/v1/costing.php`:60 — `$hasCategory = material_inventory_has_columns($pdo);`
- `includes/material_inventory.php`:523 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:595 — `if (!material_inventory_has_columns($pdo)) {`
- `includes/material_inventory.php`:2457 — `if (material_inventory_has_columns($pdo)) {`

## material_inventory_has_pool_columns {#material_inventory_has_pool_columns}

**Callers (3):**

- `includes/fulfillment_packing.php`:713 — `$poolCol = material_inventory_has_pool_columns($pdo) ? ', s.pool_yield_qty, s.pool_yield_unit' : '';`
- `includes/material_inventory.php`:535 — `$poolCols = material_inventory_has_pool_columns($pdo)`
- `includes/material_inventory.php`:858 — `if (!material_inventory_has_pool_columns($pdo)) {`

## material_inventory_has_barcode_column {#material_inventory_has_barcode_column}

**Callers (5):**

- `api/v1/materials.php`:429 — `if (material_inventory_has_barcode_column($pdo)) {`
- `includes/material_inventory.php`:365 — `if ($stockId < 1 || !material_inventory_has_barcode_column($pdo)) {`
- `includes/material_inventory.php`:538 — `$barcodeCol = material_inventory_has_barcode_column($pdo) ? ', s.barcode' : '';`
- `includes/material_inventory.php`:571 — `&& material_inventory_has_barcode_column($pdo)`
- `includes/material_inventory.php`:2529 — `if (!material_inventory_has_barcode_column($pdo)) {`

## material_inventory_has_supply_category {#material_inventory_has_supply_category}

**Callers (8):**

- `includes/material_inventory.php`:199 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:216 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:245 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:273 — `if ($row['supply_category_name'] === null && $row['supply_category_id'] !== null && material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:327 — `if (!material_inventory_has_supply_category($pdo)) {`
- `includes/material_inventory.php`:539 — `$categoryCol = material_inventory_has_supply_category($pdo)`
- `includes/material_inventory.php`:543 — `$categoryJoin = material_inventory_has_supply_category($pdo)`
- `includes/material_inventory.php`:630 — `if ($category === 'packaging' && material_inventory_has_supply_category($pdo)) {`

## material_supply_categories_list {#material_supply_categories_list}

**Tables:** `packaging_supply_categories`

**Callers (1):**

- `api/v1/materials.php`:174 — `api_ok(['categories' => material_supply_categories_list(ims_pdo())]);`

## material_supply_category_create {#material_supply_category_create}

**Tables:** `packaging_supply_categories`

**Callers (1):**

- `api/v1/materials.php`:185 — `$row = material_supply_category_create(ims_pdo(), (int) $user['id'], $name);`

## material_supply_category_id_or_null {#material_supply_category_id_or_null}

**Tables:** `packaging_supply_categories`

**Callers (3):**

- `includes/material_inventory.php`:330 — `$categoryId = material_supply_category_id_or_null($pdo, $categoryId);`
- `includes/material_inventory.php`:612 — `? material_supply_category_id_or_null($pdo, $supplyCategoryId)`
- `includes/material_inventory.php`:702 — `$params[] = material_supply_category_id_or_null($pdo, $supplyCategoryId);`

## material_inventory_attach_supply_category {#material_inventory_attach_supply_category}

**Tables:** `packaging_supply_categories`

**Callers (1):**

- `includes/material_inventory.php`:517 — `return $row ? material_inventory_attach_supply_category($pdo, $row) : null;`

## material_inventory_has_supply_note {#material_inventory_has_supply_note}

**Callers (4):**

- `includes/material_inventory.php`:542 — `$noteCol = material_inventory_has_supply_note($pdo) ? ', s.supply_note' : '';`
- `includes/material_inventory.php`:614 — `$supplyNote = material_inventory_has_supply_note($pdo)`
- `includes/material_inventory.php`:634 — `if (material_inventory_has_supply_note($pdo)) {`
- `includes/material_inventory.php`:705 — `if (!material_inventory_has_supply_note($pdo)) {`

## material_supply_note_or_null {#material_supply_note_or_null}

**Callers (2):**

- `includes/material_inventory.php`:615 — `? material_supply_note_or_null($supplyNote)`
- `includes/material_inventory.php`:709 — `$params[] = material_supply_note_or_null($supplyNote);`

## material_supply_category_assign_many {#material_supply_category_assign_many}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:204 — `$updated = material_supply_category_assign_many(ims_pdo(), $categoryId, $stockIds);`

## material_packaging_barcode_for_id {#material_packaging_barcode_for_id}

**Callers (5):**

- `includes/material_inventory.php`:366 — `return material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:372 — `return material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:379 — `return material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:381 — `$code = material_packaging_barcode_for_id($stockId);`
- `includes/material_inventory.php`:483 — `$barcode = material_packaging_barcode_for_id((int) $row['id']);`

## material_packaging_ensure_barcode {#material_packaging_ensure_barcode}

**Tables:** `stocks`

**Callers (5):**

- `api/v1/materials.php`:430 — `$row['barcode'] = material_packaging_ensure_barcode($pdo, $stockId);`
- `includes/material_inventory.php`:574 — `$row['barcode'] = material_packaging_ensure_barcode($pdo, $sid);`
- `includes/material_inventory.php`:647 — `material_packaging_ensure_barcode($pdo, $id);`
- `includes/material_inventory.php`:2556 — `$row['barcode'] = material_packaging_ensure_barcode($pdo, $id);`
- `includes/material_inventory.php`:2614 — `$code = material_packaging_ensure_barcode($pdo, $id);`

## material_packaging_last_lot_qty {#material_packaging_last_lot_qty}

**Tables:** `stock_entries`

**Callers (3):**

- `api/v1/materials.php`:432 — `$lot = material_packaging_last_lot_qty($pdo, $stockId);`
- `includes/material_inventory.php`:2534 — `$lot = material_packaging_last_lot_qty($pdo, $id);`
- `includes/material_inventory.php`:2558 — `$lot = material_packaging_last_lot_qty($pdo, $id);`

## material_packaging_last_lot_qty_map {#material_packaging_last_lot_qty_map}

**Tables:** `stock_entries`

**Callers (1):**

- `includes/material_inventory.php`:565 — `$lotMap = material_packaging_last_lot_qty_map($pdo, $packagingIds);`

## material_inventory_enrich_packaging_row {#material_inventory_enrich_packaging_row}

**Callers (5):**

- `api/v1/materials.php`:433 — `$row = material_inventory_enrich_packaging_row($row, $lot);`
- `api/v1/materials.php`:671 — `'stock' => $row !== null ? material_inventory_enrich_packaging_row($row) : null,`
- `includes/material_inventory.php`:576 — `$row = material_inventory_enrich_packaging_row($row, $lotQty);`
- `includes/material_inventory.php`:2536 — `return material_inventory_enrich_packaging_row($row, $lot);`
- `includes/material_inventory.php`:2560 — `return material_inventory_enrich_packaging_row($row, $lot);`

## material_inventory_fetch_stock {#material_inventory_fetch_stock}

**Tables:** `stocks`, `departments`

**Callers (37):**

- `api/v1/materials.php`:256 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:425 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:503 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:651 — `$stockRow = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:668 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:691 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:722 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `api/v1/materials.php`:792 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/dept_production.php`:318 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:279 — `$row = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/fulfillment_packing.php`:355 — `$stockRow = material_inventory_fetch_stock($pdo, $sid);`
- `includes/fulfillment_packing.php`:444 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:468 — `$stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);`
- `includes/fulfillment_packing.php`:595 — `$stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);`
- `includes/fulfillment_packing.php`:645 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:777 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/fulfillment_packing.php`:1029 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/inventory_check.php`:1432 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/inventory_check_apply.php`:369 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:649 — `$row = material_inventory_fetch_stock($pdo, $id);`
- `includes/material_inventory.php`:668 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:716 — `$updated = material_inventory_fetch_stock($pdo, $stockId);`
- `includes/material_inventory.php`:741 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:795 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- `includes/material_inventory.php`:864 — `$row = material_inventory_fetch_stock($pdo, $stockId, true);`
- _… and 12 more (run impact.php for full list)_

## material_inventory_list {#material_inventory_list}

**Tables:** `stock_entries`, `stocks`, `packaging_supply_categories`, `departments`

**Callers (2):**

- `api/v1/materials.php`:71 — `$items = material_inventory_list(`
- `includes/material_inventory.php`:2600 — `$items = material_inventory_list($pdo, 'packaging', true);`

## material_inventory_create {#material_inventory_create}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:115 — `$row = material_inventory_create(`

## material_inventory_update {#material_inventory_update}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:153 — `$row = material_inventory_update(`
- `includes/material_inventory.php`:1458 — `$updated = material_inventory_update($pdo, $stockId, null, null, false);`

## material_inventory_adjust {#material_inventory_adjust}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:230 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);`
- `includes/material_inventory.php`:2339 — `$result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);`

## material_packaging_set_values {#material_packaging_set_values}

**Tables:** `stock_entries`, `stocks`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_packaging_set_pool_yield {#material_packaging_set_pool_yield}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:660 — `$result = material_packaging_set_pool_yield(`
- `includes/material_inventory.php`:1053 — `material_packaging_set_pool_yield($pdo, $stockId, $yield, 'foam_piece', $userId);`

## material_parse_lot_batch_label {#material_parse_lot_batch_label}

**Callers (3):**

- `includes/material_inventory.php`:917 — `return material_parse_lot_batch_label($label);`
- `includes/material_inventory.php`:936 — `$parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);`
- `includes/material_inventory.php`:1796 — `$parsed = material_parse_lot_batch_label($label);`

## material_packaging_parse_lot_batch_label {#material_packaging_parse_lot_batch_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## material_pool_unit_cost_from_lots {#material_pool_unit_cost_from_lots}

**Tables:** `stock_entries`

**Callers (4):**

- `includes/fulfillment_packing.php`:1068 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/material_inventory.php`:961 — `return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);`
- `includes/material_inventory.php`:1254 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`
- `includes/material_inventory.php`:2039 — `$derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);`

## material_packaging_pool_unit_cost_from_lots {#material_packaging_pool_unit_cost_from_lots}

**Callers (3):**

- `includes/material_inventory.php`:1125 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/material_inventory.php`:1152 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`
- `includes/parcel_pack_costing.php`:453 — `$lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);`

## material_packaging_is_bubble_sized_stock_name {#material_packaging_is_bubble_sized_stock_name}

**Callers (1):**

- `includes/parcel_pack_costing.php`:446 — `if ($lineUnit === 'bubble_piece' && material_packaging_is_bubble_sized_stock_name($stockName)) {`

## material_packaging_is_foam_sized_stock_name {#material_packaging_is_foam_sized_stock_name}

**Callers (1):**

- `includes/parcel_pack_costing.php`:449 — `if ($lineUnit === 'foam_piece' && material_packaging_is_foam_sized_stock_name($stockName)) {`

## material_packaging_generic_foam_roll_stock_name {#material_packaging_generic_foam_roll_stock_name}

**Callers (1):**

- `includes/material_inventory.php`:981 — `return strtolower(trim($name)) === strtolower(material_packaging_generic_foam_roll_stock_name());`

## material_packaging_is_generic_foam_roll_name {#material_packaging_is_generic_foam_roll_name}

**Callers (3):**

- `api/v1/materials.php`:436 — `$isGenericFoam = material_packaging_is_generic_foam_roll_name((string) $row['name']);`
- `api/v1/materials.php`:504 — `if ($row === null || !material_packaging_is_generic_foam_roll_name((string) $row['name'])) {`
- `api/v1/materials.php`:655 — `if (material_packaging_is_generic_foam_roll_name((string) $stockRow['name'])) {`

## material_packaging_foam_cacao_cut_stock_names {#material_packaging_foam_cacao_cut_stock_names}

**Callers (2):**

- `includes/material_inventory.php`:1003 — `foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {`
- `includes/material_inventory.php`:1042 — `foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {`

## material_packaging_foam_cacao_cut_yields {#material_packaging_foam_cacao_cut_yields}

**Tables:** `stocks`

**Callers (2):**

- `api/v1/materials.php`:452 — `$payload['foam_cacao_cut_yields'] = material_packaging_foam_cacao_cut_yields($pdo);`
- `api/v1/materials.php`:524 — `'foam_cacao_cut_yields' => material_packaging_foam_cacao_cut_yields($pdo),`

## material_packaging_set_foam_cacao_cut_yields {#material_packaging_set_foam_cacao_cut_yields}

**Tables:** `stocks`

**Callers (1):**

- `api/v1/materials.php`:509 — `$result = material_packaging_set_foam_cacao_cut_yields(`

## material_inventory_is_fulfillment_parcel_stock_name {#material_inventory_is_fulfillment_parcel_stock_name}

**Callers (1):**

- `includes/material_inventory.php`:1100 — `if ($cat === 'packaging' && material_inventory_is_fulfillment_parcel_stock_name((string) ($row['name'] ?? ''))) {`

## material_inventory_eligible_for_product_formula_row {#material_inventory_eligible_for_product_formula_row}

**Callers (3):**

- `api/v1/costing.php`:91 — `if (!material_inventory_eligible_for_product_formula_row($row)) {`
- `api/v1/costing.php`:418 — `if (!material_inventory_eligible_for_product_formula_row($stock)) {`
- `api/v1/costing.php`:454 — `if (!material_inventory_eligible_for_product_formula_row($stock)) {`

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

- `api/v1/materials.php`:809 — `$result = material_packaging_stock_in_lot(`

## material_packaging_set_on_hand {#material_packaging_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (3):**

- `api/v1/materials.php`:730 — `$result = material_packaging_set_on_hand(`
- `includes/inventory_check_apply.php`:382 — `material_packaging_set_on_hand(`
- `includes/material_inventory.php`:2325 — `$result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_packaging_deduct_for_use {#material_packaging_deduct_for_use}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:552 — `$result = material_packaging_deduct_for_use(`

## material_packaging_verify_remove_password {#material_packaging_verify_remove_password}

**Tables:** `users`

**Callers (1):**

- `includes/material_inventory.php`:1445 — `if (!material_packaging_verify_remove_password($pdo, $confirmPassword)) {`

## material_packaging_catalog_remove {#material_packaging_catalog_remove}

**Callers (1):**

- `api/v1/materials.php`:474 — `$result = material_packaging_catalog_remove($pdo, $stockId, $password);`

## material_packaging_report_damage {#material_packaging_report_damage}

**Tables:** `stock_entries`, `packaging_material_damage_log`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:593 — `$result = material_packaging_report_damage(`

## material_packaging_damage_totals {#material_packaging_damage_totals}

**Tables:** `packaging_material_damage_log`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:620 — `$totals = material_packaging_damage_totals(ims_pdo(), $from, $to);`

## material_fulfillment_snap_sixteenths {#material_fulfillment_snap_sixteenths}

**Callers (6):**

- `includes/fulfillment_packing.php`:116 — `return material_fulfillment_snap_sixteenths($pieces / $yield);`
- `includes/fulfillment_packing.php`:661 — `$deductQty = material_fulfillment_snap_sixteenths($qty / $yield);`
- `includes/fulfillment_packing.php`:663 — `$deductQty = material_fulfillment_snap_sixteenths($qty);`
- `includes/fulfillment_packing.php`:795 — `$rollQty = material_fulfillment_snap_sixteenths($qty);`
- `includes/material_inventory.php`:1624 — `$deductQty = material_fulfillment_snap_sixteenths($qty / $yield);`
- `includes/material_inventory.php`:1626 — `$deductQty = material_fulfillment_snap_sixteenths($qty);`

## material_packaging_deduct_fulfillment_line {#material_packaging_deduct_fulfillment_line}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:1710 — `$legacy = material_packaging_deduct_fulfillment_line(`

## parcel_fulfillment_already_deducted {#parcel_fulfillment_already_deducted}

**Tables:** `online_parcel_fulfillment_deduct_lines`

**Callers (1):**

- `includes/material_inventory.php`:1686 — `if (parcel_fulfillment_already_deducted($pdo, $parcelId)) {`

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

- `includes/material_inventory.php`:1819 — `return material_pool_recent_entries($pdo, $stockId, $limit);`
- `includes/material_inventory.php`:1970 — `return material_pool_recent_entries($pdo, $stockId, $limit);`

## material_packaging_recent_entries {#material_packaging_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:435 — `$entries = material_packaging_recent_entries($pdo, $stockId, $logLimit);`

## material_packaging_stock_out_history {#material_packaging_stock_out_history}

**Tables:** `stock_entries`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:1033 — `api_ok(material_packaging_stock_out_history(ims_pdo(), $filters, $page, $perPage));`

## material_supply_recent_entries {#material_supply_recent_entries}

**Callers (1):**

- `api/v1/materials.php`:698 — `$entries = material_supply_recent_entries($pdo, $stockId, $logLimit);`

## material_supply_stock_in_lot {#material_supply_stock_in_lot}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/materials.php`:819 — `$result = material_supply_stock_in_lot(`

## material_supply_set_on_hand {#material_supply_set_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `api/v1/materials.php`:738 — `$result = material_supply_set_on_hand(`
- `includes/material_inventory.php`:2337 — `$result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);`

## material_supply_apply_issue {#material_supply_apply_issue}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/material_inventory.php`:2328 — `$result = material_supply_apply_issue(`

## material_adjustment_submit {#material_adjustment_submit}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:282 — `$id = material_adjustment_submit(`

## material_adjustment_list {#material_adjustment_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:307 — `$requests = material_adjustment_list($pdo, $requestedBy, $category ?: null, $requestAction ?: null);`

## material_adjustment_summary {#material_adjustment_summary}

**Callers (2):**

- `includes/material_inventory.php`:2275 — `$row['summary'] = material_adjustment_summary($row);`
- `includes/material_inventory.php`:2421 — `$row['summary'] = material_adjustment_summary($row);`

## material_adjustment_approve {#material_adjustment_approve}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:327 — `material_adjustment_approve($pdo, $id, (int) $user['id']);`

## material_adjustment_reject {#material_adjustment_reject}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:348 — `material_adjustment_reject($pdo, $id, (int) $user['id'], $note);`

## material_adjustment_pending_count {#material_adjustment_pending_count}

**Tables:** `material_adjustment_requests`

**Callers (2):**

- `api/v1/honey.php`:769 — `$count += material_adjustment_pending_count($pdo);`
- `api/v1/materials.php`:364 — `api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);`

## material_adjustment_mark_viewed {#material_adjustment_mark_viewed}

**Tables:** `material_adjustment_requests`

**Callers (1):**

- `api/v1/materials.php`:374 — `material_adjustment_mark_viewed(ims_pdo(), $id);`

## material_adjustment_unread_list {#material_adjustment_unread_list}

**Tables:** `material_adjustment_requests`, `stocks`, `users`

**Callers (1):**

- `api/v1/materials.php`:384 — `$requests = material_adjustment_unread_list(ims_pdo(), $limit);`

## material_raw_snapshot {#material_raw_snapshot}

**Tables:** `honey_warehouse_rows`, `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:392 — `api_ok(material_raw_snapshot(ims_pdo()));`

## material_inventory_raw_stock_by_slug {#material_inventory_raw_stock_by_slug}

**Tables:** `stock_entries`, `stocks`, `departments`

**Callers (1):**

- `api/v1/materials.php`:400 — `$row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);`

## material_packaging_lookup_by_barcode {#material_packaging_lookup_by_barcode}

**Tables:** `stocks`, `departments`

**Callers (3):**

- `api/v1/materials.php`:941 — `$row = material_packaging_lookup_by_barcode(ims_pdo(), $code);`
- `includes/inventory_check.php`:399 — `$mat = material_packaging_lookup_by_barcode($pdo, $code);`
- `includes/inventory_check.php`:688 — `$supply = material_packaging_lookup_by_barcode($pdo, $code);`

## material_packaging_label_cell_html {#material_packaging_label_cell_html}

**Callers (1):**

- `includes/material_inventory.php`:2618 — `'html' => material_packaging_label_cell_html($code, $name),`

## material_packaging_print_sheet_html {#material_packaging_print_sheet_html}

**Callers (1):**

- `api/v1/materials.php`:919 — `$html = material_packaging_print_sheet_html(ims_pdo(), $onlyIds);`

## material_packaging_access_roster {#material_packaging_access_roster}

**Tables:** `users`, `roles`

**Callers (3):**

- `api/v1/materials.php`:961 — `api_ok(material_packaging_access_roster(ims_pdo()));`
- `includes/material_inventory.php`:2772 — `'roster' => material_packaging_access_roster($pdo),`
- `includes/material_inventory.php`:2791 — `'roster' => material_packaging_access_roster($pdo),`

## material_packaging_deduct_candidates {#material_packaging_deduct_candidates}

**Tables:** `users`, `roles`

**Callers (1):**

- `api/v1/materials.php`:978 — `api_ok(['candidates' => material_packaging_deduct_candidates(ims_pdo())]);`

## material_packaging_deduct_require_staff_user {#material_packaging_deduct_require_staff_user}

**Tables:** `users`, `roles`

**Callers (2):**

- `includes/material_inventory.php`:2763 — `$target = material_packaging_deduct_require_staff_user($pdo, $targetUserId);`
- `includes/material_inventory.php`:2782 — `$target = material_packaging_deduct_require_staff_user($pdo, $targetUserId);`

## material_packaging_deduct_assign {#material_packaging_deduct_assign}

**Callers (1):**

- `api/v1/materials.php`:988 — `$res = material_packaging_deduct_assign(`

## material_packaging_deduct_unassign {#material_packaging_deduct_unassign}

**Callers (1):**

- `api/v1/materials.php`:1006 — `$res = material_packaging_deduct_unassign(`

