---
title: rts_damaged_use
source: includes/rts_damaged_use.php
generated: 2026-06-24T10:30:48+02:00
type: module
function_count: 4
---


# rts_damaged_use

> Source: `includes/rts_damaged_use.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/catalog_bootstrap.php`
- `includes/honey_volume.php`
- `includes/honey_inventory.php`
- `includes/dept_production.php`
- `includes/rts_inventory_sync.php`

## rts_damaged_total_bottles {#rts_damaged_total_bottles}

**Callers (1):**

- `includes/rts_damaged_use.php`:236 — `$bottlesResealed = rts_damaged_total_bottles($deductLines);`

## rts_damaged_deduct_cap_seals {#rts_damaged_deduct_cap_seals}

**Tables:** `departments`, `stocks`

**Callers (1):**

- `includes/rts_damaged_use.php`:237 — `$packagingDeduct = rts_damaged_deduct_cap_seals(`

## rts_inventory_credit_sellable_repack {#rts_inventory_credit_sellable_repack}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `includes/rts_damaged_use.php`:243 — `$sellableRowId = rts_inventory_credit_sellable_repack($pdo, $row, $deductLines);`

## rts_damaged_use_apply {#rts_damaged_use_apply}

**Tables:** `rts_inventory_rows`, `honey_production_rows`, `rts_damaged_use_events`

**Callers (1):**

- `api/v1/rts.php`:182 — `$result = rts_damaged_use_apply(`

