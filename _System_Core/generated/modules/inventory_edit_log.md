---
title: inventory_edit_log
source: includes/inventory_edit_log.php
generated: 2026-09-04T03:39:32+02:00
type: module
function_count: 3
---


# inventory_edit_log

> Source: `includes/inventory_edit_log.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## inventory_edit_log_has_source_column {#inventory_edit_log_has_source_column}

**Tables:** `inventory_edit_log`

**Callers (3):**

- `includes/inventory_edit_log.php`:38 — `if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {`
- `includes/inventory_edit_log.php`:77 — `if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {`
- `includes/inventory_edit_log.php`:96 — `$hasSource = inventory_edit_log_has_source_column($pdo);`

## inventory_edit_log_write {#inventory_edit_log_write}

**Tables:** `inventory_edit_log`

**Callers (8):**

- `includes/dept_inventory.php`:673 — `inventory_edit_log_write(`
- `includes/honey_inventory.php`:799 — `inventory_edit_log_write($pdo, $userId, $rowTable, $rowId, $field, $old, $new, $source);`
- `includes/material_inventory.php`:941 — `inventory_edit_log_write(`
- `includes/material_inventory.php`:1412 — `inventory_edit_log_write(`
- `includes/rts_inventory_sync.php`:741 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, $curCol, (string) $old, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:757 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, $curCol, (string) $have, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:827 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, 'current_qty', (string) $old, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:842 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, 'current_qty', (string) $have, (string) $new, 'monthly_inventory');`

## inventory_edit_log_list {#inventory_edit_log_list}

**Tables:** `inventory_edit_log`, `users`

**Callers (1):**

- `api/v1/honey.php`:1344 — `$result = inventory_edit_log_list(`

