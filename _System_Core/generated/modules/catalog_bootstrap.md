---
title: catalog_bootstrap
source: includes/catalog_bootstrap.php
generated: 2026-09-08T09:45:24+02:00
type: module
function_count: 8
---


# catalog_bootstrap

> Source: `includes/catalog_bootstrap.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_volume.php`

## catalog_honey_bottle_stock_name {#catalog_honey_bottle_stock_name}

**Callers (2):**

- `includes/catalog_bootstrap.php`:120 — `$bottleName = catalog_honey_bottle_stock_name($productSize);`
- `includes/honey_inventory.php`:738 — `$bottleName = catalog_honey_bottle_stock_name($size);`

## catalog_honey_raw_stock_name {#catalog_honey_raw_stock_name}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## catalog_honey_formula_liters {#catalog_honey_formula_liters}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## catalog_stock_is_pool_packaging {#catalog_stock_is_pool_packaging}

**Callers (2):**

- `includes/catalog_bootstrap.php`:91 — `} elseif (catalog_stock_is_pool_packaging($stock)) {`
- `includes/catalog_bootstrap.php`:103 — `if (!catalog_stock_is_pool_packaging($stock)) {`

## catalog_pool_consume_unit_price {#catalog_pool_consume_unit_price}

**Callers (1):**

- `includes/catalog_bootstrap.php`:92 — `$unit = catalog_pool_consume_unit_price($stock);`

## catalog_formula_line_cost {#catalog_formula_line_cost}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## catalog_pool_estimated_remaining {#catalog_pool_estimated_remaining}

**Callers (1):**

- `includes/material_inventory.php`:166 — `$row['pool_estimated_remaining'] = catalog_pool_estimated_remaining($row);`

## catalog_honey_packaging_formula_qty {#catalog_honey_packaging_formula_qty}

**Callers (1):**

- `api/v1/_costing_helpers.php`:128 — `$honeyPackQty = catalog_honey_packaging_formula_qty($stockName, $size);`

