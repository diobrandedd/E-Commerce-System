---
date: 2026-11-11
status: approved
area: system-logs
---

# Plan: Unified system Logs page

Implement [[specs/2026-11-11_system-activity-logs]].

## Phases

1. Migration phase97 + `includes/system_activity_log.php`
2. Permission `logs.view`, API `system_logs.php`, route `/logs`, Sidebar **Logs**
3. Mirror hooks on inventory edits, suppliers, deliveries, CSR finalize, POS, RTS, bulk sales, staff/settings
4. SPA SystemLogsPage with category/search/dates/pagination
5. Memory bank + verify

## Human gate

Run `sql/migration_phase97_system_activity_log.sql` on production.
