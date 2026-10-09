---
title: dept_brc_settings
source: includes/dept_brc_settings.php
generated: 2026-10-09T06:33:56+02:00
type: module
function_count: 8
---


# dept_brc_settings

> Source: `includes/dept_brc_settings.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`

## dept_brc_setting_parse_bool {#dept_brc_setting_parse_bool}

**Callers (1):**

- `includes/dept_brc_settings.php`:48 — `return dept_brc_setting_parse_bool(app_setting_get($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`

## dept_brc_setting_bag_pool_deduct_enabled {#dept_brc_setting_bag_pool_deduct_enabled}

**Callers (3):**

- `includes/dept_brc_settings.php`:137 — `'bag_pool_deduct' => dept_brc_setting_bag_pool_deduct_enabled($pdo),`
- `includes/dept_pouch_barcodes.php`:629 — `if (dept_brc_setting_bag_pool_deduct_enabled($pdo)) {`
- `includes/sku_barcodes.php`:1102 — `if (dept_brc_setting_bag_pool_deduct_enabled($pdo)) {`

## dept_brc_setting_grams_per_pouch {#dept_brc_setting_grams_per_pouch}

**Callers (4):**

- `includes/dept_brc_settings.php`:105 — `$gPouch = dept_brc_setting_grams_per_pouch($pdo);`
- `includes/dept_brc_settings.php`:129 — `$gPouch = dept_brc_setting_grams_per_pouch($pdo);`
- `includes/dept_pouch_barcodes.php`:641 — `'grams_deducted' => dept_brc_setting_grams_per_pouch($pdo),`
- `includes/sku_barcodes.php`:1108 — `'grams_deducted' => round(dept_brc_setting_grams_per_pouch($pdo) * $qty, 2),`

## dept_brc_setting_grams_per_bag {#dept_brc_setting_grams_per_bag}

**Callers (4):**

- `includes/dept_brc_settings.php`:106 — `$gBag = dept_brc_setting_grams_per_bag($pdo);`
- `includes/dept_brc_settings.php`:130 — `$gBag = dept_brc_setting_grams_per_bag($pdo);`
- `includes/dept_pouch_barcodes.php`:628 — `$gramsPerBag = dept_brc_setting_grams_per_bag($pdo);`
- `includes/sku_barcodes.php`:1101 — `$gramsPerBag = dept_brc_setting_grams_per_bag($pdo);`

## dept_brc_setting_freebie_grams {#dept_brc_setting_freebie_grams}

**Callers (2):**

- `includes/dept_brc_settings.php`:138 — `'freebie_grams' => dept_brc_setting_freebie_grams($pdo),`
- `includes/dept_brc_settings.php`:169 — `$freebie = $freebieGrams !== null ? $freebieGrams : dept_brc_setting_freebie_grams($pdo);`

## dept_brc_setting_bags_per_pouch {#dept_brc_setting_bags_per_pouch}

**Callers (3):**

- `includes/dept_brc_settings.php`:136 — `'bags_per_pouch' => dept_brc_setting_bags_per_pouch($pdo),`
- `includes/dept_pouch_barcodes.php`:630 — `$bagsPerPouch = dept_brc_setting_bags_per_pouch($pdo);`
- `includes/sku_barcodes.php`:1103 — `$bags = dept_brc_setting_bags_per_pouch($pdo) * $qty;`

## dept_brc_bom_snapshot {#dept_brc_bom_snapshot}

**Callers (6):**

- `api/v1/settings.php`:338 — `api_ok(dept_brc_bom_snapshot(ims_pdo()));`
- `api/v1/settings.php`:363 — `api_ok(array_merge($saved, dept_brc_bom_snapshot(ims_pdo())));`
- `includes/dept_brc_settings.php`:180 — `$snap = dept_brc_bom_snapshot($pdo);`
- `includes/dept_inventory.php`:267 — `$bom = dept_brc_bom_snapshot($pdo);`
- `includes/dept_inventory.php`:796 — `$bom = dept_brc_bom_snapshot($pdo);`
- `includes/dept_production.php`:995 — `$bom = dept_brc_bom_snapshot($pdo);`

## dept_brc_bom_save {#dept_brc_bom_save}

**Callers (1):**

- `api/v1/settings.php`:351 — `$saved = dept_brc_bom_save(`

