---
title: dept_products
source: includes/dept_products.php
generated: 2026-06-17T10:42:32+02:00
type: module
function_count: 21
---


# dept_products

> Source: `includes/dept_products.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## dept_pt_tea_bags_per_bag {#dept_pt_tea_bags_per_bag}

**Callers (7):**

- `includes/dept_inventory.php`:575 — `'tea_bags_per_bag' => $def['key'] === 'pt' ? dept_pt_tea_bags_per_bag() : null,`
- `includes/dept_production.php`:224 — `$teaBagsDerived = round($qty * dept_pt_tea_bags_per_bag(), 6);`
- `includes/dept_production.php`:512 — `$consumeQty = round($qty * dept_pt_tea_bags_per_bag(), 6);`
- `includes/dept_production.php`:557 — `$derivedTeaBags = round($qty * dept_pt_tea_bags_per_bag(), 2);`
- `includes/dept_production.php`:581 — `$teaBags = round($bags * dept_pt_tea_bags_per_bag(), 2);`
- `includes/dept_products.php`:50 — `return dept_pt_tea_bags_per_bag() / PT_TEA_BAGS_PER_POUCH;`
- `includes/dept_products.php`:55 — `return PT_TEA_BAGS_PER_POUCH / dept_pt_tea_bags_per_bag();`

## dept_pt_pouches_per_bag {#dept_pt_pouches_per_bag}

**Callers (1):**

- `includes/dept_products.php`:88 — `'pt' => dept_pt_pouches_per_bag(),`

## dept_pt_bags_per_pouch {#dept_pt_bags_per_pouch}

**Callers (1):**

- `includes/dept_products.php`:104 — `'pt' => dept_pt_bags_per_pouch(),`

## dept_brc_pouches_per_bag {#dept_brc_pouches_per_bag}

**Callers (1):**

- `includes/dept_products.php`:89 — `'brc' => dept_brc_pouches_per_bag(),`

## dept_brc_bags_per_pouch {#dept_brc_bags_per_pouch}

**Callers (2):**

- `includes/dept_production.php`:158 — `'qty' => round($qty * dept_brc_bags_per_pouch(), 6),`
- `includes/dept_products.php`:105 — `'brc' => dept_brc_bags_per_pouch(),`

## dept_jar_cacao_jars_per_bag {#dept_jar_cacao_jars_per_bag}

**Callers (1):**

- `includes/dept_products.php`:90 — `'jar_cacao' => dept_jar_cacao_jars_per_bag(),`

## dept_jar_cacao_bags_per_jar {#dept_jar_cacao_bags_per_jar}

**Callers (2):**

- `includes/dept_production.php`:176 — `'qty' => round($qty * dept_jar_cacao_bags_per_jar(), 6),`
- `includes/dept_products.php`:106 — `'jar_cacao' => dept_jar_cacao_bags_per_jar(),`

## dept_product_uses_supplier_pricing {#dept_product_uses_supplier_pricing}

**Callers (3):**

- `includes/dept_inventory.php`:89 — `if (dept_product_uses_supplier_pricing($productKey)) {`
- `includes/dept_pricing.php`:14 — `if ($supplierId < 1 || !dept_product_uses_supplier_pricing($productKey)) {`
- `includes/dept_pricing.php`:104 — `if (!dept_product_uses_supplier_pricing($productKey)) {`

## dept_product_sell_units_per_bag {#dept_product_sell_units_per_bag}

**Callers (6):**

- `includes/dept_inventory.php`:236 — `'sell_units_per_bag' => dept_product_sell_units_per_bag($productKey),`
- `includes/dept_inventory.php`:395 — `$sellUnitsAdded = round($qtyBags * dept_product_sell_units_per_bag($productKey), 2);`
- `includes/dept_inventory.php`:586 — `? dept_product_sell_units_per_bag((string) $def['key'])`
- `includes/dept_inventory.php`:589 — `? dept_product_sell_units_per_bag((string) $def['key'])`
- `includes/dept_products.php`:95 — `/** @deprecated Use dept_product_sell_units_per_bag() */`
- `includes/dept_products.php`:98 — `return dept_product_sell_units_per_bag($key);`

## dept_product_pouches_per_bag {#dept_product_pouches_per_bag}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_product_bags_per_sell_unit {#dept_product_bags_per_sell_unit}

**Callers (3):**

- `includes/dept_products.php`:111 — `/** @deprecated Use dept_product_bags_per_sell_unit() */`
- `includes/dept_products.php`:114 — `return dept_product_bags_per_sell_unit($key);`
- `includes/dept_products.php`:287 — `return dept_product_bags_per_sell_unit((string) $def['key']);`

## dept_product_bags_per_pouch {#dept_product_bags_per_pouch}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_product_catalog {#dept_product_catalog}

**Callers (6):**

- `includes/dashboard_inventory.php`:141 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_inventory.php`:547 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_pricing.php`:161 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_products.php`:178 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_products.php`:200 — `foreach (dept_product_catalog() as $def) {`
- `includes/dept_products.php`:246 — `foreach (dept_product_catalog() as $def) {`

## dept_product_by_key {#dept_product_by_key}

**Callers (24):**

- `api/v1/honey.php`:575 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:12 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:40 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:77 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:146 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:166 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:217 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:309 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:387 — `$def = dept_product_by_key($productKey);`
- `includes/dept_inventory.php`:502 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pos.php`:57 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pos.php`:70 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pricing.php`:49 — `$def = dept_product_by_key($productKey);`
- `includes/dept_pricing.php`:234 — `$def = dept_product_by_key($productKey);`
- `includes/dept_production.php`:368 — `$def = dept_product_by_key($productKey);`
- `includes/dept_production.php`:397 — `$def = dept_product_by_key($productKey);`
- `includes/dept_production.php`:696 — `$def = dept_product_by_key('brc');`
- `includes/dept_production.php`:748 — `$def = dept_product_by_key('jar_cacao');`
- `includes/dept_products.php`:80 — `$def = dept_product_by_key($key);`
- `includes/dept_products.php`:189 — `return dept_product_by_key($key) !== null;`
- `includes/dept_products.php`:229 — `return dept_product_by_key('pt');`
- `includes/dept_products.php`:232 — `return dept_product_by_key('brc');`
- `includes/dept_products.php`:235 — `return dept_product_by_key('jar_cacao');`
- `includes/dept_products.php`:280 — `$def = dept_product_by_key($productKey);`

## dept_product_key_valid {#dept_product_key_valid}

**Callers (4):**

- `api/v1/honey.php`:613 — `if (!dept_product_key_valid($productKey)) {`
- `api/v1/honey.php`:783 — `if (!dept_product_key_valid($productKey)) {`
- `includes/dept_pos.php`:44 — `if (!dept_product_key_valid($productKey) || $qty <= 0) {`
- `includes/honey_parcel_inventory.php`:34 — `if ($key === '' || !dept_product_key_valid($key)) {`

## dept_product_resolve_label {#dept_product_resolve_label}

**Callers (2):**

- `includes/dept_inventory.php`:538 — `$def = dept_product_resolve_label($label);`
- `includes/dept_products.php`:238 — `return dept_product_resolve_label($segment);`

## dept_product_resolve_segment {#dept_product_resolve_segment}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:179 — `$deptDef = dept_product_resolve_segment($segment);`

## dept_products_for_dept {#dept_products_for_dept}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_product_dept_id {#dept_product_dept_id}

**Tables:** `departments`

**Callers (12):**

- `includes/dept_inventory.php`:16 — `$deptId = dept_product_dept_id($pdo, $def['dept']);`
- `includes/dept_inventory.php`:44 — `$deptId = dept_product_dept_id($pdo, $def['dept']);`
- `includes/dept_inventory.php`:150 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:170 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:274 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:313 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:445 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_inventory.php`:556 — `$deptId = dept_product_dept_id($pdo, $def['dept']);`
- `includes/dept_pricing.php`:180 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_pricing.php`:242 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_production.php`:372 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`
- `includes/dept_production.php`:401 — `$deptId = dept_product_dept_id($pdo, (string) $def['dept']);`

## dept_pricing_supplier_category_for_product {#dept_pricing_supplier_category_for_product}

**Callers (2):**

- `includes/dept_pricing.php`:107 — `$expectedCategory = dept_pricing_supplier_category_for_product($productKey);`
- `includes/dept_pricing.php`:166 — `$expectedCategory = dept_pricing_supplier_category_for_product($productKey);`

## dept_product_formula_qty_for_stock {#dept_product_formula_qty_for_stock}

**Callers (1):**

- `api/v1/_costing_helpers.php`:118 — `$deptQty = dept_product_formula_qty_for_stock($deptKey, $stockName);`

