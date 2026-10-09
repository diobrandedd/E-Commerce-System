---
date: 2026-10-08
type: problem-record
status: fixed
area: packaging-inventory
tags: [packaging, damage, stock-out, scan]
---

# Problem: Packaging materials had no report-damage path

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Staff could scan stock out packaging for use, but damaged packaging had no dedicated report flow. Manual Deduct for use duplicated scan stock-out. Row damage totals with day/week/custom filters were missing.

## Cause

→ [[causes/packaging-no-damage-pool]]

## Fix

→ [[fixes/packaging-report-damage]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase91_packaging_material_damage_log.sql` |
| Includes | `includes/material_inventory.php` |
| API | `api/v1/materials.php` |
| SPA | `PackagingMaterialsPage.tsx`, `PackagingReportDamageModal.tsx`, `PackagingDamageConfirmModal.tsx` |
| Spec | [[specs/2026-10-08_packaging-report-damage]] |

## Deploy

- Run phase 91 SQL on each environment
- `cd app && npm run build` + hard-refresh Apache `app/dist/`
- Memory bank already regenerated via verify

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Packaging and materials inventory pages]]
- **Roles:** [[fixes/packaging-inventory-roles]]
