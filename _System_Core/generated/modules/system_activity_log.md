---
title: system_activity_log
source: includes/system_activity_log.php
generated: 2026-11-17T06:08:39+01:00
type: module
function_count: 2
---


# system_activity_log

> Source: `includes/system_activity_log.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## system_activity_log_write {#system_activity_log_write}

**Tables:** `system_activity_log`

**Callers (18):**

- `api/v1/costing.php`:259 — `system_activity_log_write(`
- `api/v1/staff.php`:174 — `system_activity_log_write(`
- `includes/admin_override_password.php`:52 — `system_activity_log_write(`
- `includes/bulk_honey_orders.php`:546 — `system_activity_log_write(`
- `includes/inventory_edit_log.php`:296 — `system_activity_log_write(`
- `includes/online_order_parcels.php`:1107 — `system_activity_log_write(`
- `includes/online_order_parcels.php`:1240 — `system_activity_log_write(`
- `includes/online_orders_generator.php`:1304 — `system_activity_log_write(`
- `includes/permissions.php`:483 — `system_activity_log_write(`
- `includes/permissions.php`:526 — `system_activity_log_write(`
- `includes/permissions.php`:562 — `system_activity_log_write(`
- `includes/pos_refund.php`:313 — `system_activity_log_write(`
- `includes/pos_unified.php`:707 — `system_activity_log_write(`
- `includes/rts_case_handlers.php`:546 — `system_activity_log_write(`
- `includes/rts_damaged_use.php`:369 — `system_activity_log_write(`
- `includes/suppliers.php`:179 — `system_activity_log_write(`
- `includes/suppliers.php`:226 — `system_activity_log_write(`
- `includes/suppliers.php`:251 — `system_activity_log_write(`

## system_activity_log_list {#system_activity_log_list}

**Tables:** `system_activity_log`, `users`

**Callers (1):**

- `api/v1/system_logs.php`:24 — `$result = system_activity_log_list(`

