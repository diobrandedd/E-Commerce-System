---
date: 2026-11-11
status: approved
area: system-logs
---

# Spec: Unified system Logs page

## Goal

Executives (and users granted `logs.view`) can open **Logs** in the sidebar and review high-value system changes across product setup, RTS, deliveries, order management, POS, sales, inventory edits, and staff/settings — with category filters, search, and pagination.

## Decisions

- Central `system_activity_log` feed **going forward**; domain tables stay source of truth and are mirrored at write time.
- No historical backfill in v1.
- No per-scan telemetry (pouches/barcodes) in v1.
- Permission `logs.view`; executives bypass via existing `ims_user_can`.

## Categories

`product_setup`, `rts`, `deliveries`, `order_management`, `pos`, `sales`, `inventory`, `staff_settings`

## UI

- Route `/logs`, nav label **Logs** (after Reports).
- Filters: category, search, date from/to; table 20/page with Previous/Next.

## Migration

`sql/migration_phase97_system_activity_log.sql`
