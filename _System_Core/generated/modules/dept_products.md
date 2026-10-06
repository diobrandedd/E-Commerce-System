---
title: dept_products
source: includes/dept_products.php
generated: 2026-10-06T05:01:54+02:00
type: module
function_count: 23
---


# dept_products

> Source: `includes/dept_products.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## dept_pt_tea_bags_per_bag {#dept_pt_tea_bags_per_bag}

**Callers (7):**

- `includes/dept_inventory.php`:750 — `'tea_bags_per_bag' => $def['key'] === 'pt' ? dept_pt_tea_bags_per_bag() : null,`
- `includes/dept_production.php`:249 — `$teaBagsDerived = round($qty * dept_pt_tea_bags_per_bag(), 6);`
- `includes/dept_production.php`:772 — `$consumeQty = round($qty * dept_pt_tea_bags_per_bag(), 6);`
- `includes/dept_production.php`:817 — `$derivedTeaBags = round($qty * dept_pt_tea_bags_per_bag(), 2);`
- `includes/dept_production.php`:841 — `$teaBags = round($bags * dept_pt_tea_bags_per_bag(), 2);`
- `includes/dept_products.php`:58 — `return dept_pt_tea_bags_per_bag() / PT_TEA_BAGS_PER_POUCH;`
- `includes/dept_products.php`:63 — `return PT_TEA_BAGS_PER_POUCH / dept_pt_tea_bags_per_bag();`

## dept_pt_pouches_per_bag {#dept_pt_pouches_per_bag}

**Callers (1):**

- `includes/dept_products.php`:106 — `'pt' => dept_pt_pouches_per_bag(),`

## dept_pt_bags_per_pouch {#dept_pt_bags_per_pouch}

**Callers (2):**

- `includes/dept_products.php`:123 — `'pt' => dept_pt_bags_per_pouch(),`
- `includes/dept_pt_settings.php`:94 — `return dept_pt_bags_per_pouch();`

## dept_brc_pouches_per_bag {#dept_brc_pouches_per_bag}

**Callers (1):**

- `includes/dept_products.php`:107 — `'brc' => dept_brc_pouches_per_bag(),`

## dept_brc_bags_per_pouch {#dept_brc_bags_per_pouch}

**Callers (3):**

- `includes/dept_brc_settings.php`:87 — `return dept_brc_bags_per_pouch();`
- `includes/dept_production.php`:158 — `'qty' => round($qty * dept_brc_bags_per_pouch(), 6),`
- `includes/dept_products.php`:124 — `'brc' => dept_brc_bags_per_pouch(),`

## dept_jar_cacao_jars_per_bag {#dept_jar_cacao_jars_per_bag}

**Callers (1):**

- `includes/dept_products.php`:108 — `'jar_cacao' => dept_jar_cacao_jars_per_bag(),`

## dept_jar_cacao_bags_per_jar {#dept_jar_cacao_bags_per_jar}

**Callers (3):**

- `includes/dept_jar_cacao_settings.php`:90 — `return dept_jar_cacao_bags_per_jar();`
- `includes/dept_production.php`:176 — `'qty' => round($qty * dept_jar_cacao_bags_per_jar(), 6),`
- `includes/dept_products.php`:125 — `'jar_cacao' => dept_jar_cacao_bags_per_jar(),`

## dept_honey_comb_jars_per_bag {#dept_honey_comb_jars_per_bag}

**Callers (1):**

- `includes/dept_products.php`:109 — `'honey_comb' => dept_honey_comb_jars_per_bag(),`

## dept_honey_comb_bags_per_jar {#dept_honey_comb_bags_per_jar}

**Callers (2):**

- `includes/dept_production.php`:194 — `'qty' => round($qty * dept_honey_comb_bags_per_jar(), 6),`
- `includes/dept_products.php`:126 — `'honey_comb' => dept_honey_comb_bags_per_jar(),`

## dept_product_uses_supplier_pricing {#dept_product_uses_supplier_pricing}

**Callers (3):**

- `includes/dept_inventory.php`:89 — `if (dept_product_uses_supplier_pricing($productKey)) {`
- `includes/dept_pricing.php`:14 — `if ($supplierId < 1 || !dept_product_uses_supplier_pricing($productKey)) {`
- `includes/dept_pricing.php`:104 — `if (!dept_product_uses_supplier_pricing($productKey)) {`

## dept_product_sell_units_per_bag {#dept_product_sell_units_per_bag}

**Callers (6):**

- `includes/dept_inventory.php`:240 — `'sell_units_per_bag' => dept_product_sell_units_per_bag($productKey),`
- `includes/dept_inventory.php`:570 — `$sellUnitsAdded = round($qtyBags * dept_product_sell_units_per_bag($productKey), 2);`
- `includes/dept_inventory.php`:766 — `? dept_product_sell_units_per_bag((string) $def['key'])`
- `includes/dept_inventory.php`:769 — `? dept_product_sell_units_per_bag((string) $def['key'])`
- `includes/dept_products.php`:114 — `/** @deprecated Use dept_product_sell_units_per_bag() */`
- `includes/dept_products.php`:117 — `return dept_product_sell_units_per_bag($key);`

## dept_product_pouches_per_bag {#dept_product_pouches_per_bag}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_product_bags_per_sell_unit {#dept_product_bags_per_sell_unit}

**Callers (3):**

- `includes/dept_products.php`:131 — `/** @deprecated Use dept_product_bags_per_sell_unit() */`
- `includes/dept_products.php`:134 — `return dept_product_bags_per_sell_unit($key);`
- `includes/dept_products.php`:325 — `return dept_product_bags_per_sell_unit((string) $def['key']);`

## dept_product_bags_per_pouch {#dept_product_bags_per_pouch}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_product_catalog {#dept_product_catalog}

**Callers (8):**

- `includes/dashboard_inventory.php`:141 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_inventory.php`:722 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_pricing.php`:161 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_products.php`:212 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_products.php`:234 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_products.php`:283 — `foreach (dept_product_catalog() as $def) {`
- `includes/honey_sales_report.php`:329 — `foreach (dept_product_catalog() as $product) {`
- `includes/honey_sales_report.php`:1250 — `foreach (dept_product_catalog() as $p) {`

## dept_product_by_key {#dept_product_by_key}

**Callers (36):**

- `api/v1/honey.php`:1030 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:12 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:40 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:77 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:148 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:168 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:219 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:373 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:447 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:520 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:562 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:677 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:826 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pos.php`:128 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pos.php`:137 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pouch_barcodes.php`:53 — `$product = dept_product_by_key($productKey);`
- `includes/dept_pricing.php`:49 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pricing.php`:234 — `$def = dept_product_by_key($productKey);`
- `includes/dept_production.php`:393 — `$def = dept_product_by_key($productKey);`
- `includes/dept_production.php`:422 — `$def = dept_product_by_key($productKey);`
- `includes/dept_production.php`:956 — `$def = dept_product_by_key('brc');`
- `includes/dept_production.php`:1035 — `$def = dept_product_by_key('jar_cacao');`
- `includes/dept_production.php`:1065 — `$def = dept_product_by_key('honey_comb');`
- `includes/dept_products.php`:98 — `$def = dept_product_by_key($key);`
- `includes/dept_products.php`:223 — `return dept_product_by_key($key) !== null;`
- _… and 11 more (run impact.php for full list)_

## dept_product_key_valid {#dept_product_key_valid}

**Callers (6):**

- `api/v1/honey.php`:1068 — `if (!dept_product_key_valid($productKey)) {`
- `api/v1/honey.php`:1208 — `if (!dept_product_key_valid($productKey)) {`
- `api/v1/honey.php`:1227 — `if (!dept_product_key_valid($productKey)) {`
- `includes/dept_pos.php`:82 — `if (!dept_product_key_valid($productKey) || $qty <= 0) {`
- `includes/honey_parcel_inventory.php`:41 — `if ($key === '' || !dept_product_key_valid($key)) {`
- `includes/pos_discount_requests.php`:18 — `return $productKey !== '' && dept_product_key_valid($productKey);`

## dept_product_resolve_label {#dept_product_resolve_label}

**Callers (2):**

- `includes/dept_inventory.php`:713 — `$def = dept_product_resolve_label($label);`
- `includes/dept_products.php`:275 — `return dept_product_resolve_label($segment);`

## dept_product_resolve_segment {#dept_product_resolve_segment}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:305 — `$deptDef = dept_product_resolve_segment($segment);`

## dept_products_for_dept {#dept_products_for_dept}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_product_dept_id {#dept_product_dept_id}

**Tables:** `departments`

**Callers (15):**

- `includes/dept_inventory.php`:16 — `$deptId = dept_product_dept_id($pdo, $def['dept']);`
- `includes/dept_inventory.php`:44 — `$deptId = dept_product_dept_id($pdo, $def['dept']);`
- `includes/dept_inventory.php`:152 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:172 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:338 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:377 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:451 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:524 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:620 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:731 — `$deptId = dept_product_dept_id($pdo, $def['dept']);`
- `includes/dept_inventory.php`:831 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_pricing.php`:180 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_pricing.php`:242 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_production.php`:397 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_production.php`:426 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`

## dept_pricing_supplier_category_for_product {#dept_pricing_supplier_category_for_product}

**Callers (2):**

- `includes/dept_pricing.php`:107 — `$expectedCategory = dept_pricing_supplier_category_for_product($productKey);`
- `includes/dept_pricing.php`:166 — `$expectedCategory = dept_pricing_supplier_category_for_product($productKey);`

## dept_product_formula_qty_for_stock {#dept_product_formula_qty_for_stock}

**Callers (1):**

- `api/v1/_costing_helpers.php`:118 — `$deptQty = dept_product_formula_qty_for_stock($deptKey, $stockName);`

