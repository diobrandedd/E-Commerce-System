---
title: app_settings
source: includes/app_settings.php
generated: 2026-10-20T09:53:50+02:00
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

**Callers (19):**

- `includes/admin_override_password.php`:12 — `$hash = app_setting_get($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, null);`
- `includes/admin_override_password.php`:22 — `$hash = app_setting_get($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, null);`
- `includes/dept_brc_settings.php`:48 — `return dept_brc_setting_parse_bool(app_setting_get($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`
- `includes/dept_brc_settings.php`:56 — `$raw = app_setting_get($pdo, BRC_GRAMS_PER_POUCH_SETTING_KEY, null);`
- `includes/dept_brc_settings.php`:73 — `$raw = app_setting_get($pdo, BRC_GRAMS_PER_BAG_SETTING_KEY, null);`
- `includes/dept_brc_settings.php`:90 — `$raw = app_setting_get($pdo, BRC_FREEBIE_GRAMS_SETTING_KEY, null);`
- `includes/dept_jar_cacao_settings.php`:49 — `app_setting_get($pdo, JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY, null),`
- `includes/dept_jar_cacao_settings.php`:59 — `$raw = app_setting_get($pdo, JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY, null);`
- `includes/dept_jar_cacao_settings.php`:76 — `$raw = app_setting_get($pdo, JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY, null);`
- `includes/dept_jar_cacao_settings.php`:93 — `$raw = app_setting_get($pdo, JAR_CACAO_FREEBIE_GRAMS_SETTING_KEY, null);`
- `includes/dept_pt_settings.php`:48 — `return dept_pt_setting_parse_bool(app_setting_get($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, null), true);`
- `includes/dept_pt_settings.php`:56 — `$raw = app_setting_get($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, null);`
- `includes/dept_pt_settings.php`:73 — `$raw = app_setting_get($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, null);`
- `includes/dept_pt_settings.php`:98 — `$raw = app_setting_get($pdo, PT_FREEBIE_GRAMS_SETTING_KEY, null);`
- `includes/dept_raw_freebies.php`:35 — `$raw = app_setting_get($pdo, dept_raw_freebie_setting_key($productKey), null);`
- `includes/honey_volume.php`:67 — `$raw = app_setting_get($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, null);`
- `includes/pyxacct_ap_sync.php`:23 — `$raw = app_setting_get($rtsPdo, 'pyxacct_honey_ap_sync_enabled', '1');`
- `includes/pyxacct_ap_sync.php`:37 — `return rtrim(trim((string) app_setting_get($rtsPdo, 'pyxacct_api_base_url', '')), '/');`
- `includes/pyxacct_ap_sync.php`:59 — `return pyxacct_api_token_normalize((string) app_setting_get($rtsPdo, 'pyxacct_api_token', ''));`

## app_setting_set {#app_setting_set}

**Tables:** `app_settings`

**Callers (19):**

- `includes/admin_override_password.php`:49 — `app_setting_set($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, $hash, $userId);`
- `includes/dept_brc_settings.php`:175 — `app_setting_set($pdo, BRC_GRAMS_PER_POUCH_SETTING_KEY, (string) $gramsPerPouch, $userId);`
- `includes/dept_brc_settings.php`:176 — `app_setting_set($pdo, BRC_GRAMS_PER_BAG_SETTING_KEY, (string) $gramsPerBag, $userId);`
- `includes/dept_brc_settings.php`:177 — `app_setting_set($pdo, BRC_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);`
- `includes/dept_brc_settings.php`:178 — `app_setting_set($pdo, BRC_FREEBIE_GRAMS_SETTING_KEY, (string) $freebie, $userId);`
- `includes/dept_jar_cacao_settings.php`:178 — `app_setting_set($pdo, JAR_CACAO_GRAMS_PER_JAR_SETTING_KEY, (string) $gramsPerJar, $userId);`
- `includes/dept_jar_cacao_settings.php`:179 — `app_setting_set($pdo, JAR_CACAO_GRAMS_PER_BAG_SETTING_KEY, (string) $gramsPerBag, $userId);`
- `includes/dept_jar_cacao_settings.php`:180 — `app_setting_set($pdo, JAR_CACAO_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);`
- `includes/dept_jar_cacao_settings.php`:181 — `app_setting_set($pdo, JAR_CACAO_FREEBIE_GRAMS_SETTING_KEY, (string) $freebie, $userId);`
- `includes/dept_pt_settings.php`:182 — `app_setting_set($pdo, PT_GRAMS_PER_TEA_BAG_SETTING_KEY, (string) $gramsPerTeaBag, $userId);`
- `includes/dept_pt_settings.php`:183 — `app_setting_set($pdo, PT_TEA_BAGS_PER_POUCH_SETTING_KEY, (string) $teaBagsPerPouch, $userId);`
- `includes/dept_pt_settings.php`:184 — `app_setting_set($pdo, PT_BAG_POOL_DEDUCT_SETTING_KEY, $bagPoolDeduct ? '1' : '0', $userId);`
- `includes/dept_pt_settings.php`:185 — `app_setting_set($pdo, PT_FREEBIE_GRAMS_SETTING_KEY, (string) $freebie, $userId);`
- `includes/dept_raw_freebies.php`:57 — `app_setting_set($pdo, dept_raw_freebie_setting_key($productKey), (string) $grams, $userId);`
- `includes/honey_volume.php`:48 — `app_setting_set($pdo, HONEY_PACKAGING_DEDUCT_SETTING_KEY, '0', $userId);`
- `includes/honey_volume.php`:91 — `app_setting_set($pdo, HONEY_ML_PER_GALLON_SETTING_KEY, $stored, $userId);`
- `includes/pyxacct_ap_sync.php`:30 — `app_setting_set($rtsPdo, 'pyxacct_honey_ap_sync_enabled', $enabled ? '1' : '0', $userId);`
- `includes/pyxacct_ap_sync.php`:75 — `app_setting_set($rtsPdo, 'pyxacct_api_base_url', $baseUrl, $userId);`
- `includes/pyxacct_ap_sync.php`:80 — `app_setting_set($rtsPdo, 'pyxacct_api_token', $token, $userId);`

