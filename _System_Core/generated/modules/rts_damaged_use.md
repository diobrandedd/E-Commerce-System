---
title: rts_damaged_use
source: includes/rts_damaged_use.php
generated: 2026-09-10T05:04:56+02:00
type: module
function_count: 6
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

## rts_damaged_use_events_list {#rts_damaged_use_events_list}

**Tables:** `rts_damaged_use_events`, `rts_inventory_rows`, `users`

**Callers (1):**

- `api/v1/rts.php`:358 — `api_ok(['rows' => rts_damaged_use_events_list(ims_pdo(), $limit)]);`

## rts_damaged_use_event_summary {#rts_damaged_use_event_summary}

**Callers (1):**

- `includes/rts_damaged_use.php`:36 — `$row['summary'] = rts_damaged_use_event_summary($row);`

## rts_damaged_total_bottles {#rts_damaged_total_bottles}

**Callers (1):**

- `includes/rts_damaged_use.php`:332 — `$bottlesResealed = rts_damaged_total_bottles($deductLines);`

## rts_damaged_deduct_cap_seals {#rts_damaged_deduct_cap_seals}

**Tables:** `departments`, `stocks`

**Callers (1):**

- `includes/rts_damaged_use.php`:333 — `$packagingDeduct = rts_damaged_deduct_cap_seals(`

## rts_inventory_credit_sellable_repack {#rts_inventory_credit_sellable_repack}

**Tables:** `rts_inventory_rows`

**Callers (1):**

- `includes/rts_damaged_use.php`:339 — `$sellableRowId = rts_inventory_credit_sellable_repack($pdo, $row, $deductLines);`

## rts_damaged_use_apply {#rts_damaged_use_apply}

**Tables:** `rts_inventory_rows`, `honey_production_rows`, `rts_damaged_use_events`

**Callers (1):**

- `api/v1/rts.php`:337 — `$result = rts_damaged_use_apply(`

