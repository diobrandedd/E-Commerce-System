---
title: dept_pt_settings
source: includes/dept_pt_settings.php
generated: 2026-09-10T07:09:38+02:00
type: module
function_count: 6
---


# dept_pt_settings

> Source: `includes/dept_pt_settings.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`

## dept_pt_setting_grams_per_tea_bag {#dept_pt_setting_grams_per_tea_bag}

**Callers (3):**

- `includes/dept_pt_settings.php`:57 — `dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:86 — `$gTea = dept_pt_setting_grams_per_tea_bag($pdo);`
- `includes/dept_pt_settings.php`:120 — `'grams_per_tea_bag' => dept_pt_setting_grams_per_tea_bag($pdo),`

## dept_pt_setting_tea_bags_per_pouch {#dept_pt_setting_tea_bags_per_pouch}

**Callers (3):**

- `includes/dept_pt_settings.php`:57 — `dept_pt_setting_grams_per_tea_bag($pdo) * dept_pt_setting_tea_bags_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:87 — `$perPouch = dept_pt_setting_tea_bags_per_pouch($pdo);`
- `includes/dept_pt_settings.php`:121 — `'tea_bags_per_pouch' => dept_pt_setting_tea_bags_per_pouch($pdo),`

## dept_pt_setting_grams_per_pouch {#dept_pt_setting_grams_per_pouch}

**Callers (4):**

- `includes/dept_pouch_barcodes.php`:603 — `'grams_deducted' => dept_pt_setting_grams_per_pouch($pdo),`
- `includes/dept_pt_settings.php`:65 — `$g = dept_pt_setting_grams_per_pouch($pdo);`
- `includes/dept_pt_settings.php`:122 — `'grams_per_pouch' => dept_pt_setting_grams_per_pouch($pdo),`
- `includes/sku_barcodes.php`:1076 — `'grams_deducted' => round(dept_pt_setting_grams_per_pouch($pdo) * $qty, 2),`

## dept_pt_setting_bags_per_pouch {#dept_pt_setting_bags_per_pouch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:592 — `$bagsPerPouch = dept_pt_setting_bags_per_pouch($pdo);`
- `includes/sku_barcodes.php`:1071 — `$bags = dept_pt_setting_bags_per_pouch($pdo) * $qty;`

## dept_pt_bom_snapshot {#dept_pt_bom_snapshot}

**Callers (4):**

- `api/v1/settings.php`:202 — `api_ok(dept_pt_bom_snapshot(ims_pdo()));`
- `api/v1/settings.php`:225 — `api_ok(array_merge($saved, dept_pt_bom_snapshot(ims_pdo())));`
- `includes/dept_inventory.php`:248 — `$bom = dept_pt_bom_snapshot($pdo);`
- `includes/dept_production.php`:986 — `$bom = dept_pt_bom_snapshot($pdo);`

## dept_pt_bom_save {#dept_pt_bom_save}

**Callers (1):**

- `api/v1/settings.php`:215 — `$saved = dept_pt_bom_save(`

