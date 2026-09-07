---
title: dept_pricing
source: includes/dept_pricing.php
generated: 2026-09-07T09:06:58+02:00
type: module
function_count: 8
---


# dept_pricing

> Source: `includes/dept_pricing.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/suppliers.php`
- `includes/honey_inventory.php`

## dept_pricing_resolve_active_price {#dept_pricing_resolve_active_price}

**Tables:** `supplier_dept_prices`

**Callers (2):**

- `includes/dept_inventory.php`:270 — `$priceRow = dept_pricing_resolve_active_price($pdo, $supplierId, $productKey, $arrivalDate);`
- `includes/dept_pricing.php`:175 — `$priceRow = dept_pricing_resolve_active_price($pdo, $supplierId, $productKey, $asOf);`

## dept_pricing_stocks_has_supplier_id {#dept_pricing_stocks_has_supplier_id}

**Callers (1):**

- `includes/dept_pricing.php`:148 — `if ($supplierId < 1 || !dept_pricing_stocks_has_supplier_id($pdo)) {`

## dept_pricing_leaf_stock_name {#dept_pricing_leaf_stock_name}

**Callers (8):**

- `includes/dept_inventory.php`:144 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_inventory.php`:167 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_inventory.php`:224 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_inventory.php`:311 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_inventory.php`:627 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_pricing.php`:170 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_pricing.php`:238 — `$stockName = dept_pricing_leaf_stock_name($productKey);`
- `includes/dept_production.php`:99 — `$leaf = dept_pricing_leaf_stock_name($productKey);`

## dept_pricing_list_supplier_prices {#dept_pricing_list_supplier_prices}

**Tables:** `supplier_dept_prices`, `suppliers`

**Callers (1):**

- `api/v1/honey.php`:1153 — `$prices = dept_pricing_list_supplier_prices(`

## dept_pricing_save_supplier_prices {#dept_pricing_save_supplier_prices}

**Tables:** `supplier_dept_prices`

**Callers (1):**

- `api/v1/honey.php`:1201 — `$saved = dept_pricing_save_supplier_prices($pdo, $supplierId, $products, $effectiveFrom, (int) $user['id']);`

## dept_pricing_upsert_supplier_costing_stocks {#dept_pricing_upsert_supplier_costing_stocks}

**Tables:** `stocks`

**Callers (2):**

- `includes/dept_inventory.php`:275 — `dept_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);`
- `includes/dept_pricing.php`:136 — `dept_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);`

## dept_pricing_recalc_products_using_stock {#dept_pricing_recalc_products_using_stock}

**Callers (1):**

- `includes/dept_pricing.php`:205 — `dept_pricing_recalc_products_using_stock($pdo, $stockId, 0.0, $pricePerBag, $userId);`

## dept_pricing_leaf_stock_id {#dept_pricing_leaf_stock_id}

**Tables:** `stocks`

**Callers (1):**

- `includes/dept_inventory.php`:276 — `$leafStockId = dept_pricing_leaf_stock_id($pdo, $supplierId, $productKey);`

