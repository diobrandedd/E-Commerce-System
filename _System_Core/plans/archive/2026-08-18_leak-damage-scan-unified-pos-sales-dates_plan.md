---
status: shipped
spec: "[[specs/2026-08-18_leak-damage-scan-unified-pos-sales-dates]]"
created: 2026-08-18
approved_by: user
---

# Implementation Plan: Leak/damage scan, Sales dates, unified POS

## Phase 1 — Leak/damage backend

- [x] `sql/migration_phase63_finished_leak_damage_log.sql`
- [x] `includes/finished_leak_damage.php` — scan + history
- [x] `api/v1/finished_leak_damage.php`
- [x] Migrate `dept_pouch_defect_log` → new table; deprecate defect API

## Phase 2 — Leak/damage UI

- [x] `FinishedLeakDamagePage.tsx` + route
- [x] Replace Jar Cacao Defect on `RawDeptMaterialPage`

## Phase 3 — Sales dates

- [x] `SalesPage.tsx` — Pick date + custom range UX

## Phase 4 — Unified POS backend

- [x] `includes/pos_unified.php` — lookup + checkout
- [x] `api/v1/pos.php` — unified_lookup, unified_checkout, unified_ar_checkout

## Phase 5 — Unified POS UI

- [x] `POSUnifiedPage.tsx`, `posUnified.ts`, routes + redirects
- [x] Update POS guide/tour

## Phase 6 — Verify + memory bank

- [x] verify.ps1, generate_all, problem record, 003 update
