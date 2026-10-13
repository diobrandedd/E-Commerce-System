---
title: inventory_edit_log
source: includes/inventory_edit_log.php
generated: 2026-10-13T10:05:59+02:00
type: module
function_count: 4
---


# inventory_edit_log

> Source: `includes/inventory_edit_log.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## inventory_edit_log_has_source_column {#inventory_edit_log_has_source_column}

**Tables:** `inventory_edit_log`

**Callers (3):**

- `includes/inventory_edit_log.php`:55 — `$hasSource = $source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo);`
- `includes/inventory_edit_log.php`:113 — `if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {`
- `includes/inventory_edit_log.php`:137 — `$hasSource = inventory_edit_log_has_source_column($pdo);`

## inventory_edit_log_has_reason_column {#inventory_edit_log_has_reason_column}

**Tables:** `inventory_edit_log`

**Callers (3):**

- `includes/inventory_edit_log.php`:56 — `$hasReason = $reason !== null && $reason !== '' && inventory_edit_log_has_reason_column($pdo);`
- `includes/inventory_edit_log.php`:127 — `if (inventory_edit_log_has_reason_column($pdo)) {`
- `includes/inventory_edit_log.php`:138 — `$hasReason = inventory_edit_log_has_reason_column($pdo);`

## inventory_edit_log_write {#inventory_edit_log_write}

**Tables:** `inventory_edit_log`

**Callers (8):**

- `includes/dept_inventory.php`:878 — `inventory_edit_log_write(`
- `includes/honey_inventory.php`:2234 — `inventory_edit_log_write($pdo, $userId, $rowTable, $rowId, $field, $old, $new, $source, $reason);`
- `includes/material_inventory.php`:1324 — `inventory_edit_log_write(`
- `includes/material_inventory.php`:2107 — `inventory_edit_log_write(`
- `includes/rts_inventory_sync.php`:741 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, $curCol, (string) $old, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:757 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, $curCol, (string) $have, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:827 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, 'current_qty', (string) $old, (string) $new, 'monthly_inventory');`
- `includes/rts_inventory_sync.php`:842 — `inventory_edit_log_write($pdo, $userId, 'rts_inventory_rows', $id, 'current_qty', (string) $have, (string) $new, 'monthly_inventory');`

## inventory_edit_log_list {#inventory_edit_log_list}

**Tables:** `inventory_edit_log`, `users`

**Callers (1):**

- `api/v1/honey.php`:1563 — `$result = inventory_edit_log_list(`

