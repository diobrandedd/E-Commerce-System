---
title: inventory_edit_log
source: includes/inventory_edit_log.php
generated: 2026-11-27T06:40:59+01:00
type: module
function_count: 6
---


# inventory_edit_log

> Source: `includes/inventory_edit_log.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/system_activity_log.php`

## inventory_edit_log_has_source_column {#inventory_edit_log_has_source_column}

**Tables:** `inventory_edit_log`

**Callers (3):**

- `includes/inventory_edit_log.php`:255 — `$hasSource = $source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo);`
- `includes/inventory_edit_log.php`:343 — `if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {`
- `includes/inventory_edit_log.php`:367 — `$hasSource = inventory_edit_log_has_source_column($pdo);`

## inventory_edit_log_has_reason_column {#inventory_edit_log_has_reason_column}

**Tables:** `inventory_edit_log`

**Callers (3):**

- `includes/inventory_edit_log.php`:256 — `$hasReason = $reason !== null && $reason !== '' && inventory_edit_log_has_reason_column($pdo);`
- `includes/inventory_edit_log.php`:357 — `if (inventory_edit_log_has_reason_column($pdo)) {`
- `includes/inventory_edit_log.php`:368 — `$hasReason = inventory_edit_log_has_reason_column($pdo);`

## inventory_edit_log_supplier_label {#inventory_edit_log_supplier_label}

**Tables:** `suppliers`

**Callers (2):**

- `includes/inventory_edit_log.php`:113 — `$oldDisplay = inventory_edit_log_supplier_label($pdo, $old) ?? $old;`
- `includes/inventory_edit_log.php`:114 — `$newDisplay = inventory_edit_log_supplier_label($pdo, $new) ?? $new;`

## inventory_edit_log_resolve_display {#inventory_edit_log_resolve_display}

**Tables:** `stocks`, `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`, `rts_inventory_rows`, `dept_inventory_rows`

**Callers (1):**

- `includes/inventory_edit_log.php`:287 — `$display = inventory_edit_log_resolve_display($pdo, $rowTable, $rowId, $field, $old, $new, $source);`

## inventory_edit_log_write {#inventory_edit_log_write}

**Tables:** `inventory_edit_log`

**Callers (8):**

- `includes/dept_inventory.php`:878 — `inventory_edit_log_write(`
- `includes/honey_inventory.php`:2301 — `inventory_edit_log_write($pdo, $userId, $rowTable, $rowId, $field, $old, $new, $source, $reason);`
- `includes/material_inventory.php`:1327 — `inventory_edit_log_write(`
- `includes/material_inventory.php`:2110 — `inventory_edit_log_write(`
- `includes/rts_inventory_sync.php`:741 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, $curCol, (string) $old, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:757 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, $curCol, (string) $have, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:827 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, 'current_qty', (string) $old, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:842 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, 'current_qty', (string) $have, (string) $new, 'monthly_inventory');`

## inventory_edit_log_list {#inventory_edit_log_list}

**Tables:** `inventory_edit_log`, `users`

**Callers (1):**

- `api/v1/honey.php`:1549 — `$result = inventory_edit_log_list(`

