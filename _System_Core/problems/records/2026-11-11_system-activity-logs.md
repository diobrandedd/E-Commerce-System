---
date: 2026-11-11
type: problem-record
status: fixed
area: system-logs
tags: [problem, audit, logs]
---

# Problem: No unified Logs page for system-wide changes

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Executives could not find a single place named Logs to review management-visible changes across product setup, RTS, deliveries, orders, POS, sales, and staff. Audit history was scattered (Edit Logs, cancel events, POS history).

## Cause

→ [[causes/no-unified-system-activity-log]]

## Fix

→ [[fixes/system-activity-logs-page]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase97_system_activity_log.sql` |
| Include | `includes/system_activity_log.php` |
| API | `api/v1/system_logs.php` |
| SPA | `app/src/app/pages/exec/SystemLogsPage.tsx`, `app/src/app/lib/systemLogs.ts` |
| Nav | `app/src/app/components/Sidebar.tsx` |
| Hooks | inventory_edit_log, suppliers, online_order_parcels, online_orders_generator, pos_unified, pos_refund, rts_case_handlers, rts_damaged_use, bulk_honey_orders, permissions, admin_override_password, staff, costing |

## Deploy

- Run phase 97 SQL on prod
- PHP + `cd app && npm run build` + hard refresh
- Grant `logs.view` for non-executive users who need access

## Links

- **Spec:** [[specs/2026-11-11_system-activity-logs]]
