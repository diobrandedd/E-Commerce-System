---
title: pyxacct_supply_sync
source: includes/pyxacct_supply_sync.php
generated: 2026-10-30T07:50:52+01:00
type: module
function_count: 3
---


# pyxacct_supply_sync

> Source: `includes/pyxacct_supply_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/pyxacct_walkin_sync.php`

## pyxacct_supply_sync_enabled {#pyxacct_supply_sync_enabled}

**Callers (2):**

- `includes/pyxacct_ap_sync.php`:97 — `'supply_enabled' => pyxacct_supply_sync_enabled($rtsPdo),`
- `includes/pyxacct_supply_sync.php`:37 — `if ($entryId < 1 || !pyxacct_supply_sync_enabled($rtsPdo)) {`

## pyxacct_supply_sync_enabled_save {#pyxacct_supply_sync_enabled_save}

**Callers (1):**

- `api/v1/settings.php`:517 — `pyxacct_supply_sync_enabled_save(`

## pyxacct_sync_supply_lot {#pyxacct_sync_supply_lot}

**Callers (1):**

- `api/v1/materials.php`:846 — `pyxacct_sync_supply_lot($pdo, [`

