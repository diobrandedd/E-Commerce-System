---
title: pyxacct_ap_sync
source: includes/pyxacct_ap_sync.php
generated: 2026-10-09T06:34:46+02:00
type: module
function_count: 8
---


# pyxacct_ap_sync

> Source: `includes/pyxacct_ap_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/app_settings.php`

## pyxacct_honey_ap_sync_enabled_on_tracker {#pyxacct_honey_ap_sync_enabled_on_tracker}

**Callers (2):**

- `includes/pyxacct_ap_sync.php`:73 — `'enabled' => pyxacct_honey_ap_sync_enabled_on_tracker($rtsPdo),`
- `includes/pyxacct_ap_sync.php`:163 — `if (!pyxacct_honey_ap_sync_enabled_on_tracker($rtsPdo)) {`

## pyxacct_honey_ap_sync_enabled_save {#pyxacct_honey_ap_sync_enabled_save}

**Callers (1):**

- `includes/pyxacct_ap_sync.php`:52 — `pyxacct_honey_ap_sync_enabled_save($rtsPdo, $enabled, $userId);`

## pyxacct_api_base_url_get {#pyxacct_api_base_url_get}

**Callers (3):**

- `includes/pyxacct_ap_sync.php`:74 — `'api_base_url' => pyxacct_api_base_url_get($rtsPdo),`
- `includes/pyxacct_ap_sync.php`:87 — `$base = pyxacct_api_base_url_get($rtsPdo);`
- `includes/pyxacct_ap_sync.php`:167 — `$base = pyxacct_api_base_url_get($rtsPdo);`

## pyxacct_api_token_get {#pyxacct_api_token_get}

**Callers (3):**

- `includes/pyxacct_ap_sync.php`:75 — `'token_set' => pyxacct_api_token_get($rtsPdo) !== '',`
- `includes/pyxacct_ap_sync.php`:88 — `$token = pyxacct_api_token_get($rtsPdo);`
- `includes/pyxacct_ap_sync.php`:168 — `$token = pyxacct_api_token_get($rtsPdo);`

## pyxacct_api_settings_save {#pyxacct_api_settings_save}

**Callers (1):**

- `api/v1/settings.php`:460 — `$public = pyxacct_api_settings_save(`

## pyxacct_api_settings_public {#pyxacct_api_settings_public}

**Callers (2):**

- `api/v1/settings.php`:441 — `api_ok(pyxacct_api_settings_public(ims_pdo()));`
- `includes/pyxacct_ap_sync.php`:66 — `return pyxacct_api_settings_public($rtsPdo);`

## pyxacct_test_connection {#pyxacct_test_connection}

**Callers (1):**

- `api/v1/settings.php`:480 — `$result = pyxacct_test_connection(ims_pdo());`

## pyxacct_sync_honey_warehouse_payable {#pyxacct_sync_honey_warehouse_payable}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `includes/honey_pricing.php`:65 — `pyxacct_sync_honey_warehouse_payable($pdo, $warehouseRowId);`

