---
title: dept_pt_settings
source: includes/dept_pt_settings.php
generated: 2026-10-20T09:54:05+02:00
type: module
function_count: 9
---


# dept_pt_settings

> Source: `includes/dept_pt_settings.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`

## dept_pt_setting_parse_bool {#dept_pt_setting_parse_bool}

**Callers (1):**

- `includes/dept_pt_settings.php`:48 — `return dept_pt_setting_parse_bool(app_setting_get($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`

## dept_pt_setting_bag_pool_deduct_enabled {#dept_pt_setting_bag_pool_deduct_enabled}

**Callers (4):**

- `includes/dept_pouch_barcodes.php`:592 — `if (dept_pt_setting_bag_pool_deduct_enabled($pdo)) {`
- `includes/dept_pt_settings.php`:147 — `'bag_pool_deduct' => dept_pt_setting_bag_pool_deduct_enabled($pdo),`
- `includes/dept_pt_settings.php`:191 — `'bag_pool_deduct' => dept_pt_setting_bag_pool_deduct_enabled($pdo),`
- `includes/sku_barcodes.php`:1071 — `if (dept_pt_setting_bag_pool_deduct_enabled($pdo)) {`

## dept_pt_setting_grams_per_tea_bag {#dept_pt_setting_grams_per_tea_bag}

**Callers (3):**

- `includes/dept_pt_settings.php`:88 — `dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:137 — `$gTea = dept_pt_setting_grams_per_tea_bag($pdo);`
- `includes/dept_pt_settings.php`:188 — `'grams_per_tea_bag' => dept_pt_setting_grams_per_tea_bag($pdo),`

## dept_pt_setting_tea_bags_per_pouch {#dept_pt_setting_tea_bags_per_pouch}

**Callers (3):**

- `includes/dept_pt_settings.php`:88 — `dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:138 — `$perPouch = dept_pt_setting_tea_bags_per_pouch($pdo);`
- `includes/dept_pt_settings.php`:189 — `'tea_bags_per_pouch' => dept_pt_setting_tea_bags_per_pouch($pdo),`

## dept_pt_setting_grams_per_pouch {#dept_pt_setting_grams_per_pouch}

**Callers (4):**

- `includes/dept_pouch_barcodes.php`:604 — `'grams_deducted' => dept_pt_setting_grams_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:113 — `$g = dept_pt_setting_grams_per_pouch($pdo);`
- `includes/dept_pt_settings.php`:190 — `'grams_per_pouch' => dept_pt_setting_grams_per_pouch($pdo),`
- `includes/sku_barcodes.php`:1077 — `'grams_deducted' => round(dept_pt_setting_grams_per_pouch($pdo) * $qty, 2),`

## dept_pt_setting_freebie_grams {#dept_pt_setting_freebie_grams}

**Callers (3):**

- `includes/dept_pt_settings.php`:148 — `'freebie_grams' => dept_pt_setting_freebie_grams($pdo),`
- `includes/dept_pt_settings.php`:176 — `$freebie = $freebieGrams !== null ? $freebieGrams : dept_pt_setting_freebie_grams($pdo);`
- `includes/dept_pt_settings.php`:192 — `'freebie_grams' => dept_pt_setting_freebie_grams($pdo),`

## dept_pt_setting_bags_per_pouch {#dept_pt_setting_bags_per_pouch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:593 — `$bagsPerPouch = dept_pt_setting_bags_per_pouch($pdo);`
- `includes/sku_barcodes.php`:1072 — `$bags = dept_pt_setting_bags_per_pouch($pdo) * $qty;`

## dept_pt_bom_snapshot {#dept_pt_bom_snapshot}

**Callers (5):**

- `api/v1/settings.php`:305 — `api_ok(dept_pt_bom_snapshot(ims_pdo()));`
- `api/v1/settings.php`:330 — `api_ok(array_merge($saved, dept_pt_bom_snapshot(ims_pdo())));`
- `includes/dept_inventory.php`:248 — `$bom = dept_pt_bom_snapshot($pdo);`
- `includes/dept_inventory.php`:790 — `$bom = dept_pt_bom_snapshot($pdo);`
- `includes/dept_production.php`:984 — `$bom = dept_pt_bom_snapshot($pdo);`

## dept_pt_bom_save {#dept_pt_bom_save}

**Callers (1):**

- `api/v1/settings.php`:318 — `$saved = dept_pt_bom_save(`

