---
date: 2026-10-08
type: problem-record
status: fixed
area: dept-raw-inventory
tags: [problem, freebies, bag-pool]
---

# Problem: No tracked freebie sample pool on raw materials

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Ops gave small sample pouches from Dry Paragis / Black Rice / Cacao leaf without a Freebies on-hand count next to finished stock, and without create / give-out history.

## Cause

→ [[causes/raw-freebie-no-pool]]

## Fix

→ [[fixes/raw-freebie-samples]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase92_dept_raw_freebies.sql` |
| Includes | `includes/dept_raw_freebies.php`, `dept_*_settings.php`, `dept_production.php` |
| API | `api/v1/honey.php`, `api/v1/settings.php` |
| SPA | `RawDeptMaterialPage.tsx`, `SettingsPage.tsx`, `deptRawInventory.ts` |
| Spec | [[specs/2026-10-08_raw-freebie-samples]] |

## Deploy

- Run phase92 SQL · `cd app && npm run build` · hard refresh · regenerate memory bank

## Links

- **Plan:** [[plans/archive/2026-10-08_raw-freebie-samples_plan]]
