---
status: shipped
spec: "[[specs/2026-10-08_raw-freebie-samples]]"
created: 2026-10-08
approved_by: chat
---

# Implementation Plan: Raw freebie samples

> Spec: [[specs/2026-10-08_raw-freebie-samples]]

## Summary

Add freebie grams to PT/BRC/Cacao BOM settings; migration for balances + log; create/give-out/history APIs; Freebies metric card + modal on bag-pool raw pages.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]
- Deduct leaf via existing `dept_raw_deduct_bags_fifo` (strict, no overdraft for freebies)
- Feedback motion on new SPA controls

## Impact preview

| Area | Change |
|------|--------|
| DB | `dept_raw_freebie_balances`, `dept_raw_freebie_log` |
| Includes | freebie helpers; BOM settings `freebie_grams` |
| API | `honey.php` freebie actions; settings BOM get/save |
| SPA | Settings fields; RawDept Freebies card + modal |

## Implementation steps

### Phase 1 — Data / backend

1. [x] Migration phase92
2. [x] Settings keys + freebie helpers
3. [x] Snapshot `freebie_on_hand` / `freebie_grams`

### Phase 2 — API

1. [x] create / give_out / history on honey.php

### Phase 3 — Frontend

1. [x] Settings UI
2. [x] Metric card + Freebies modal

### Phase 4 — Cleanup

1. [ ] verify + memory bank
2. [ ] archive plan

## Files to touch (expected)

| File | Action |
|------|--------|
| `sql/migration_phase92_dept_raw_freebies.sql` | create |
| `includes/dept_raw_freebies.php` | create |
| `includes/dept_pt_settings.php` etc. | modify |
| `includes/dept_inventory.php` / `dept_production.php` | modify |
| `api/v1/honey.php`, `settings.php` | modify |
| `app/src/.../SettingsPage.tsx`, `RawDeptMaterialPage.tsx`, `deptRawInventory.ts` | modify |
