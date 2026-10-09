---
title: dept_jar_cacao_settings
source: includes/dept_jar_cacao_settings.php
generated: 2026-10-09T06:31:50+02:00
type: module
function_count: 8
---


# dept_jar_cacao_settings

> Source: `includes/dept_jar_cacao_settings.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`

## dept_jar_cacao_setting_parse_bool {#dept_jar_cacao_setting_parse_bool}

**Callers (1):**

- `includes/dept_jar_cacao_settings.php`:48 — `return dept_jar_cacao_setting_parse_bool(`

## dept_jar_cacao_setting_bag_pool_deduct_enabled {#dept_jar_cacao_setting_bag_pool_deduct_enabled}

**Callers (3):**

- `includes/dept_jar_cacao_settings.php`:140 — `'bag_pool_deduct' => dept_jar_cacao_setting_bag_pool_deduct_enabled($pdo),`
- `includes/dept_pouch_barcodes.php`:666 — `if (dept_jar_cacao_setting_bag_pool_deduct_enabled($pdo)) {`
- `includes/sku_barcodes.php`:1133 — `if (dept_jar_cacao_setting_bag_pool_deduct_enabled($pdo)) {`

## dept_jar_cacao_setting_grams_per_jar {#dept_jar_cacao_setting_grams_per_jar}

**Callers (4):**

- `includes/dept_jar_cacao_settings.php`:108 — `$gJar = dept_jar_cacao_setting_grams_per_jar($pdo);`
- `includes/dept_jar_cacao_settings.php`:132 — `$gJar = dept_jar_cacao_setting_grams_per_jar($pdo);`
- `includes/dept_pouch_barcodes.php`:678 — `'grams_deducted' => dept_jar_cacao_setting_grams_per_jar($pdo),`
- `includes/sku_barcodes.php`:1139 — `'grams_deducted' => round(dept_jar_cacao_setting_grams_per_jar($pdo) * $qty, 2),`

## dept_jar_cacao_setting_grams_per_bag {#dept_jar_cacao_setting_grams_per_bag}

**Callers (4):**

- `includes/dept_jar_cacao_settings.php`:109 — `$gBag = dept_jar_cacao_setting_grams_per_bag($pdo);`
- `includes/dept_jar_cacao_settings.php`:133 — `$gBag = dept_jar_cacao_setting_grams_per_bag($pdo);`
- `includes/dept_pouch_barcodes.php`:665 — `$gramsPerBag = dept_jar_cacao_setting_grams_per_bag($pdo);`
- `includes/sku_barcodes.php`:1132 — `$gramsPerBag = dept_jar_cacao_setting_grams_per_bag($pdo);`

## dept_jar_cacao_setting_freebie_grams {#dept_jar_cacao_setting_freebie_grams}

**Callers (2):**

- `includes/dept_jar_cacao_settings.php`:141 — `'freebie_grams' => dept_jar_cacao_setting_freebie_grams($pdo),`
- `includes/dept_jar_cacao_settings.php`:172 — `$freebie = $freebieGrams !== null ? $freebieGrams : dept_jar_cacao_setting_freebie_grams($pdo);`

## dept_jar_cacao_setting_bags_per_jar {#dept_jar_cacao_setting_bags_per_jar}

**Callers (3):**

- `includes/dept_jar_cacao_settings.php`:139 — `'bags_per_jar' => dept_jar_cacao_setting_bags_per_jar($pdo),`
- `includes/dept_pouch_barcodes.php`:667 — `$bagsPerJar = dept_jar_cacao_setting_bags_per_jar($pdo);`
- `includes/sku_barcodes.php`:1134 — `$bags = dept_jar_cacao_setting_bags_per_jar($pdo) * $qty;`

## dept_jar_cacao_bom_snapshot {#dept_jar_cacao_bom_snapshot}

**Callers (6):**

- `api/v1/settings.php`:371 — `api_ok(dept_jar_cacao_bom_snapshot(ims_pdo()));`
- `api/v1/settings.php`:396 — `api_ok(array_merge($saved, dept_jar_cacao_bom_snapshot(ims_pdo())));`
- `includes/dept_inventory.php`:286 — `$bom = dept_jar_cacao_bom_snapshot($pdo);`
- `includes/dept_inventory.php`:802 — `$bom = dept_jar_cacao_bom_snapshot($pdo);`
- `includes/dept_jar_cacao_settings.php`:183 — `$snap = dept_jar_cacao_bom_snapshot($pdo);`
- `includes/dept_production.php`:1006 — `$bom = dept_jar_cacao_bom_snapshot($pdo);`

## dept_jar_cacao_bom_save {#dept_jar_cacao_bom_save}

**Callers (1):**

- `api/v1/settings.php`:384 — `$saved = dept_jar_cacao_bom_save(`

