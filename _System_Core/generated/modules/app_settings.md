---
title: app_settings
source: includes/app_settings.php
generated: 2026-09-10T07:09:27+02:00
type: module
function_count: 3
---


# app_settings

> Source: `includes/app_settings.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## app_settings_table_ready {#app_settings_table_ready}

**Callers (2):**

- `includes/app_settings.php`:28 — `if ($key === '' || !app_settings_table_ready($pdo)) {`
- `includes/app_settings.php`:47 — `if (!app_settings_table_ready($pdo)) {`

## app_setting_get {#app_setting_get}

**Tables:** `app_settings`

**Callers (3):**

- `includes/dept_pt_settings.php`:25 — `$raw = app_setting_get($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, null);`
- `includes/dept_pt_settings.php`:42 — `$raw = app_setting_get($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, null);`
- `includes/honey_volume.php`:30 — `$raw = app_setting_get($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, null);`

## app_setting_set {#app_setting_set}

**Tables:** `app_settings`

**Callers (3):**

- `includes/dept_pt_settings.php`:116 — `app_setting_set($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, (string) $gramsPerTeaBag, $userId);`
- `includes/dept_pt_settings.php`:117 — `app_setting_set($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, (string) $teaBagsPerPouch, $userId);`
- `includes/honey_volume.php`:54 — `app_setting_set($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, $stored, $userId);`

