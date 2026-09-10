---
date: 2026-09-10
type: problem-record
status: fixed
area: honey-packaging
tags: [honey, packaging, bottling-date, uniqueness, stock-in]
---

# Problem: Packaging lots collapsed across bottling days

> Same batch+variant reused one packaging row; later stock-ins kept the original bottling date and ops could not filter by lot date.

## Symptom

Stocking JR12345 dark on a later day added qty to yesterday’s packaging row instead of creating a dated lot. Packaging UI filtered by scan date only.

## Cause

→ [[causes/packaging-unique-batch-variant-only]]

## Fix

→ [[fixes/packaging-unique-batch-variant-date]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase77_honey_packaging_batch_variant_date.sql` |
| Primary | `includes/honey_inventory.php` · `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` · `api/v1/honey.php` |
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` · `app/src/app/lib/bottleBarcodes.ts` |
| Spec / plan | [[specs/2026-09-10_packaging-bottling-date-unique]] · [[plans/archive/2026-09-10_packaging-bottling-date-unique_plan]] |

## Related

- Fragile edge: [[003_Fragile_Edges#Honey packaging batch_no uniqueness]]
- UX: [[005_SPA_UX_Patterns#Raw Honey stage tabs]]
