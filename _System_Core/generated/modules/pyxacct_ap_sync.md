---
title: pyxacct_ap_sync
source: includes/pyxacct_ap_sync.php
generated: 2026-10-30T07:50:51+01:00
type: module
function_count: 9
---


# pyxacct_ap_sync

> Source: `includes/pyxacct_ap_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/app_settings.php`
- `includes/pyxacct_walkin_sync.php`
- `includes/pyxacct_supply_sync.php`

## pyxacct_honey_ap_sync_enabled_on_tracker {#pyxacct_honey_ap_sync_enabled_on_tracker}

**Callers (2):**

- `includes/pyxacct_ap_sync.php`:93 — `'enabled' => pyxacct_honey_ap_sync_enabled_on_tracker($rtsPdo),`
- `includes/pyxacct_ap_sync.php`:188 — `if (!pyxacct_honey_ap_sync_enabled_on_tracker($rtsPdo)) {`

## pyxacct_honey_ap_sync_enabled_save {#pyxacct_honey_ap_sync_enabled_save}

**Callers (1):**

- `includes/pyxacct_ap_sync.php`:69 — `pyxacct_honey_ap_sync_enabled_save($rtsPdo, $enabled, $userId);`

## pyxacct_api_base_url_get {#pyxacct_api_base_url_get}

**Callers (7):**

- `includes/pyxacct_ap_sync.php`:94 — `'api_base_url' => pyxacct_api_base_url_get($rtsPdo),`
- `includes/pyxacct_ap_sync.php`:109 — `$base = pyxacct_api_base_url_get($rtsPdo);`
- `includes/pyxacct_ap_sync.php`:192 — `$base = pyxacct_api_base_url_get($rtsPdo);`
- `includes/pyxacct_supply_sync.php`:53 — `$base = pyxacct_api_base_url_get($rtsPdo);`
- `includes/pyxacct_walkin_sync.php`:136 — `$base = pyxacct_api_base_url_get($rtsPdo);`
- `includes/pyxacct_walkin_sync.php`:181 — `$base = pyxacct_api_base_url_get($rtsPdo);`
- `includes/pyxacct_walkin_sync.php`:208 — `$base = pyxacct_api_base_url_get($rtsPdo);`

## pyxacct_api_token_normalize {#pyxacct_api_token_normalize}

**Callers (2):**

- `includes/pyxacct_ap_sync.php`:59 — `return pyxacct_api_token_normalize((string) app_setting_get($rtsPdo, 'pyxacct_api_token', ''));`
- `includes/pyxacct_ap_sync.php`:78 — `$token = pyxacct_api_token_normalize($token);`

## pyxacct_api_token_get {#pyxacct_api_token_get}

**Callers (7):**

- `includes/pyxacct_ap_sync.php`:95 — `'token_set' => pyxacct_api_token_get($rtsPdo) !== '',`
- `includes/pyxacct_ap_sync.php`:110 — `$token = pyxacct_api_token_get($rtsPdo);`
- `includes/pyxacct_ap_sync.php`:193 — `$token = pyxacct_api_token_get($rtsPdo);`
- `includes/pyxacct_supply_sync.php`:54 — `$token = pyxacct_api_token_get($rtsPdo);`
- `includes/pyxacct_walkin_sync.php`:137 — `$token = pyxacct_api_token_get($rtsPdo);`
- `includes/pyxacct_walkin_sync.php`:182 — `$token = pyxacct_api_token_get($rtsPdo);`
- `includes/pyxacct_walkin_sync.php`:209 — `$token = pyxacct_api_token_get($rtsPdo);`

## pyxacct_api_settings_save {#pyxacct_api_settings_save}

**Callers (1):**

- `api/v1/settings.php`:464 — `$public = pyxacct_api_settings_save(`

## pyxacct_api_settings_public {#pyxacct_api_settings_public}

**Callers (4):**

- `api/v1/settings.php`:445 — `api_ok(pyxacct_api_settings_public(ims_pdo()));`
- `api/v1/settings.php`:505 — `api_ok(array_merge(pyxacct_api_settings_public(ims_pdo()), ['saved' => true]));`
- `api/v1/settings.php`:522 — `api_ok(array_merge(pyxacct_api_settings_public(ims_pdo()), ['saved' => true]));`
- `includes/pyxacct_ap_sync.php`:83 — `return pyxacct_api_settings_public($rtsPdo);`

## pyxacct_test_connection {#pyxacct_test_connection}

**Callers (1):**

- `api/v1/settings.php`:484 — `$result = pyxacct_test_connection(ims_pdo());`

## pyxacct_sync_honey_warehouse_payable {#pyxacct_sync_honey_warehouse_payable}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `includes/honey_pricing.php`:65 — `pyxacct_sync_honey_warehouse_payable($pdo, $warehouseRowId);`

