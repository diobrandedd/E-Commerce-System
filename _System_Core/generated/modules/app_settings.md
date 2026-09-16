---
title: app_settings
source: includes/app_settings.php
generated: 2026-09-16T12:00:39+02:00
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

**Callers (13):**

- `includes/admin_override_password.php`:12 — `$hash = app_setting_get($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, null);`
- `includes/admin_override_password.php`:22 — `$hash = app_setting_get($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, null);`
- `includes/dept_brc_settings.php`:44 — `return dept_brc_setting_parse_bool(app_setting_get($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`
- `includes/dept_brc_settings.php`:52 — `$raw = app_setting_get($pdo, BRC_GRAMS_PER_POUCH_SETTING_KEY, null);`
- `includes/dept_brc_settings.php`:69 — `$raw = app_setting_get($pdo, BRC_GRAMS_PER_BAG_SETTING_KEY, null);`
- `includes/dept_jar_cacao_settings.php`:45 — `app_setting_get($pdo, JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY, null),`
- `includes/dept_jar_cacao_settings.php`:55 — `$raw = app_setting_get($pdo, JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY, null);`
- `includes/dept_jar_cacao_settings.php`:72 — `$raw = app_setting_get($pdo, JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY, null);`
- `includes/dept_pt_settings.php`:44 — `return dept_pt_setting_parse_bool(app_setting_get($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`
- `includes/dept_pt_settings.php`:52 — `$raw = app_setting_get($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, null);`
- `includes/dept_pt_settings.php`:69 — `$raw = app_setting_get($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, null);`
- `includes/honey_volume.php`:44 — `return honey_setting_parse_bool(app_setting_get($pdo, HONEY_PACKAGING_DEDUCT_SETTING_KEY, null), true);`
- `includes/honey_volume.php`:67 — `$raw = app_setting_get($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, null);`

## app_setting_set {#app_setting_set}

**Tables:** `app_settings`

**Callers (12):**

- `includes/admin_override_password.php`:49 — `app_setting_set($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, $hash, $userId);`
- `includes/dept_brc_settings.php`:143 — `app_setting_set($pdo, BRC_GRAMS_PER_POUCH_SETTING_KEY, (string) $gramsPerPouch, $userId);`
- `includes/dept_brc_settings.php`:144 — `app_setting_set($pdo, BRC_GRAMS_PER_BAG_SETTING_KEY, (string) $gramsPerBag, $userId);`
- `includes/dept_brc_settings.php`:145 — `app_setting_set($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);`
- `includes/dept_jar_cacao_settings.php`:146 — `app_setting_set($pdo, JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY, (string) $gramsPerJar, $userId);`
- `includes/dept_jar_cacao_settings.php`:147 — `app_setting_set($pdo, JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY, (string) $gramsPerBag, $userId);`
- `includes/dept_jar_cacao_settings.php`:148 — `app_setting_set($pdo, JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);`
- `includes/dept_pt_settings.php`:150 — `app_setting_set($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, (string) $gramsPerTeaBag, $userId);`
- `includes/dept_pt_settings.php`:151 — `app_setting_set($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, (string) $teaBagsPerPouch, $userId);`
- `includes/dept_pt_settings.php`:152 — `app_setting_set($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);`
- `includes/honey_volume.php`:49 — `app_setting_set($pdo, HONEY_PACKAGING_DEDUCT_SETTING_KEY, $enabled ? '1' : '0', $userId);`
- `includes/honey_volume.php`:91 — `app_setting_set($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, $stored, $userId);`

