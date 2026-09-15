---
title: dept_pt_settings
source: includes/dept_pt_settings.php
generated: 2026-09-15T10:42:27+02:00
type: module
function_count: 8
---


# dept_pt_settings

> Source: `includes/dept_pt_settings.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`

## dept_pt_setting_parse_bool {#dept_pt_setting_parse_bool}

**Callers (1):**

- `includes/dept_pt_settings.php`:44 — `return dept_pt_setting_parse_bool(app_setting_get($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`

## dept_pt_setting_bag_pool_deduct_enabled {#dept_pt_setting_bag_pool_deduct_enabled}

**Callers (4):**

- `includes/dept_pouch_barcodes.php`:592 — `if (dept_pt_setting_bag_pool_deduct_enabled($pdo)) {`
- `includes/dept_pt_settings.php`:124 — `'bag_pool_deduct' => dept_pt_setting_bag_pool_deduct_enabled($pdo),`
- `includes/dept_pt_settings.php`:158 — `'bag_pool_deduct' => dept_pt_setting_bag_pool_deduct_enabled($pdo),`
- `includes/sku_barcodes.php`:1071 — `if (dept_pt_setting_bag_pool_deduct_enabled($pdo)) {`

## dept_pt_setting_grams_per_tea_bag {#dept_pt_setting_grams_per_tea_bag}

**Callers (3):**

- `includes/dept_pt_settings.php`:84 — `dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:114 — `$gTea = dept_pt_setting_grams_per_tea_bag($pdo);`
- `includes/dept_pt_settings.php`:155 — `'grams_per_tea_bag' => dept_pt_setting_grams_per_tea_bag($pdo),`

## dept_pt_setting_tea_bags_per_pouch {#dept_pt_setting_tea_bags_per_pouch}

**Callers (3):**

- `includes/dept_pt_settings.php`:84 — `dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:115 — `$perPouch = dept_pt_setting_tea_bags_per_pouch($pdo);`
- `includes/dept_pt_settings.php`:156 — `'tea_bags_per_pouch' => dept_pt_setting_tea_bags_per_pouch($pdo),`

## dept_pt_setting_grams_per_pouch {#dept_pt_setting_grams_per_pouch}

**Callers (4):**

- `includes/dept_pouch_barcodes.php`:604 — `'grams_deducted' => dept_pt_setting_grams_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:92 — `$g = dept_pt_setting_grams_per_pouch($pdo);`
- `includes/dept_pt_settings.php`:157 — `'grams_per_pouch' => dept_pt_setting_grams_per_pouch($pdo),`
- `includes/sku_barcodes.php`:1077 — `'grams_deducted' => round(dept_pt_setting_grams_per_pouch($pdo) * $qty, 2),`

## dept_pt_setting_bags_per_pouch {#dept_pt_setting_bags_per_pouch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:593 — `$bagsPerPouch = dept_pt_setting_bags_per_pouch($pdo);`
- `includes/sku_barcodes.php`:1072 — `$bags = dept_pt_setting_bags_per_pouch($pdo) * $qty;`

## dept_pt_bom_snapshot {#dept_pt_bom_snapshot}

**Callers (4):**

- `api/v1/settings.php`:299 — `api_ok(dept_pt_bom_snapshot(ims_pdo()));`
- `api/v1/settings.php`:323 — `api_ok(array_merge($saved, dept_pt_bom_snapshot(ims_pdo())));`
- `includes/dept_inventory.php`:248 — `$bom = dept_pt_bom_snapshot($pdo);`
- `includes/dept_production.php`:986 — `$bom = dept_pt_bom_snapshot($pdo);`

## dept_pt_bom_save {#dept_pt_bom_save}

**Callers (1):**

- `api/v1/settings.php`:312 — `$saved = dept_pt_bom_save(`

