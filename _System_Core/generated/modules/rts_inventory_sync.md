---
title: rts_inventory_sync
source: includes/rts_inventory_sync.php
generated: 2026-06-19T07:05:47+02:00
type: module
function_count: 5
---


# rts_inventory_sync

> Source: `includes/rts_inventory_sync.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/honey_inventory.php`

## rts_inv_product_label {#rts_inv_product_label}

**Callers (1):**

- `includes/rts_inventory_sync.php`:67 — `$label = rts_inv_product_label($productType, $li['combo_product'] ?? null);`

## rts_inv_is_honey_line {#rts_inv_is_honey_line}

**Callers (1):**

- `includes/rts_inventory_sync.php`:65 — `$isHoney = rts_inv_is_honey_line($productType);`

## rts_inventory_sync_run {#rts_inventory_sync_run}

**Tables:** `rts_sync_state`, `rts_line_items`, `rts_cases`, `rts_inventory_rows`

**Callers (2):**

- `api/v1/rts_cases.php`:60 — `rts_inventory_sync_run($pdo);`
- `api/v1/rts_sync.php`:28 — `$result = rts_inventory_sync_run(ims_pdo());`

## rts_inventory_sync_status {#rts_inventory_sync_status}

**Tables:** `rts_sync_state`

**Callers (3):**

- `api/v1/rts.php`:124 — `$sync = rts_inventory_sync_status($pdo);`
- `api/v1/rts.php`:176 — `api_ok(rts_inventory_sync_status(ims_pdo()));`
- `api/v1/rts_sync.php`:39 — `api_ok(rts_inventory_sync_status(ims_pdo()));`

## rts_inventory_deduct_sell {#rts_inventory_deduct_sell}

**Tables:** `rts_inventory_rows`, `honey_sales_log`

**Callers (1):**

- `api/v1/rts.php`:158 — `rts_inventory_deduct_sell(`

