---
status: approved
spec: "[[specs/2026-08-25_check-inventory-stock-take]]"
created: 2026-08-25
approved_by: user (chat "go ahead" 2026-08-25)
---

# Implementation Plan: Check Inventory (physical stock take)

> Spec: [[specs/2026-08-25_check-inventory-stock-take]]

## Summary

New `inventory_check` module: shared daily count event, barcode scanning for honey + all dept lines, scanned summary with per-batch drilldown, compare against system inventory scoped to counted lines, and dated history.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]] — count rows are their own record; do not derive history from current qty
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| DB | phase67: `inventory_check_events`, `inventory_check_scans`, `inventory_check_compare_runs`, `inventory_check_variance_lines` + backfill `inventory.check.scan` |
| Includes | new `includes/inventory_check.php` |
| API | new `api/v1/inventory_check.php` |
| Permissions | `inventory.check.scan` / `.view_all` / `.manage` |
| SPA | `/inventory/check` page, client, scan log modal, route, sidebar |

## Implementation steps

### Phase 1 — Data / backend

1. [x] Migration phase67 (tables + permission backfill) — applied
2. [x] Permission catalog + role presets; regenerate `permissionKeys.ts`
3. [x] `includes/inventory_check.php`: event lifecycle, scan, remove, summary, scan log
4. [x] Compare run + variance persistence

### Phase 2 — API

1. [x] `api/v1/inventory_check.php` actions with CSRF + gating

### Phase 3 — Frontend

1. [x] `InventoryCheckPage` (Scan always; Summary/Compare/History when `view_all`)
2. [x] `InventoryCheckScanLogModal`, `lib/inventoryCheck.ts`, route + sidebar

### Phase 4 — Integration

1. [x] Verifier loop — PHP lint, SPA build, memory bank pass; CLI smoke of open → scan → summary → compare → close
2. [x] Regenerate memory bank; problem record; append `002_Memory_Bank.md`
3. [x] Archive this plan

## Deploy

- Run `sql/migration_phase67_inventory_check.sql`
- Confirm `inventory.check.scan` on existing staff; grant `view_all` + `manage` to exec / prod manager
- Rebuild SPA and hard-refresh
