---
title: pyxacct_walkin_sync
source: includes/pyxacct_walkin_sync.php
generated: 2026-10-30T07:50:52+01:00
type: module
function_count: 7
---


# pyxacct_walkin_sync

> Source: `includes/pyxacct_walkin_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/pyxacct_ap_sync.php`
- `includes/rts_schema.php`

## pyxacct_walkin_sync_enabled {#pyxacct_walkin_sync_enabled}

**Callers (4):**

- `includes/pyxacct_ap_sync.php`:96 — `'walkin_enabled' => pyxacct_walkin_sync_enabled($rtsPdo),`
- `includes/pyxacct_walkin_sync.php`:133 — `if (!pyxacct_walkin_sync_enabled($rtsPdo)) {`
- `includes/pyxacct_walkin_sync.php`:178 — `if ($saleId < 1 || !pyxacct_walkin_sync_enabled($rtsPdo)) {`
- `includes/pyxacct_walkin_sync.php`:205 — `if (!pyxacct_walkin_sync_enabled($rtsPdo)) {`

## pyxacct_walkin_sync_enabled_save {#pyxacct_walkin_sync_enabled_save}

**Callers (1):**

- `api/v1/settings.php`:500 — `pyxacct_walkin_sync_enabled_save(`

## pyxacct_api_post {#pyxacct_api_post}

**Callers (5):**

- `includes/pyxacct_supply_sync.php`:59 — `$result = pyxacct_api_post($base, $token, 'ingest_supply', [`
- `includes/pyxacct_walkin_sync.php`:157 — `$result = pyxacct_api_post($base, $token, 'ingest_walkin', [`
- `includes/pyxacct_walkin_sync.php`:186 — `$result = pyxacct_api_post($base, $token, 'void_walkin', [`
- `includes/pyxacct_walkin_sync.php`:245 — `$result = pyxacct_api_post($base, $token, 'void_walkin', ['source_key' => $sourceKey]);`
- `includes/pyxacct_walkin_sync.php`:263 — `$result = pyxacct_api_post($base, $token, 'ingest_walkin', [`

## pyxacct_walkin_load_sale {#pyxacct_walkin_load_sale}

**Tables:** `pos_sales`

**Callers (1):**

- `includes/pyxacct_walkin_sync.php`:141 — `$sale = pyxacct_walkin_load_sale($rtsPdo, $saleId);`

## pyxacct_sync_walkin_sale {#pyxacct_sync_walkin_sale}

**Callers (2):**

- `includes/honey_pos.php`:493 — `pyxacct_sync_walkin_sale($pdo, $saleId);`
- `includes/pos_discount_requests.php`:282 — `pyxacct_sync_walkin_sale($pdo, (int) $result['sale_id']);`

## pyxacct_void_walkin_sale {#pyxacct_void_walkin_sale}

**Callers (2):**

- `includes/pos_refund.php`:282 — `pyxacct_void_walkin_sale($pdo, $saleId);`
- `includes/pyxacct_walkin_sync.php`:150 — `pyxacct_void_walkin_sale($rtsPdo, $sale['id']);`

## pyxacct_sync_existing_walkin {#pyxacct_sync_existing_walkin}

**Tables:** `pos_sales`

**Callers (1):**

- `api/v1/settings.php`:533 — `$counts = pyxacct_sync_existing_walkin(ims_pdo());`

