---
date: 2026-06-19
type: problem-record
status: fixed
area: fulfillment-packaging
tags: [packaging, staging, bubble]
---

# Problem: Sized bubble staging showed whole rolls, not cut pieces

## Symptom

After issuing 1 bubble roll as 17 cm and reporting damage, For packing showed **Issued 1 / Damaged 1 / Remaining 0** in **rolls**. Ops expect piece-level counts (e.g. ~204 pc per 17 cm roll) matching the packaging spreadsheet (`17 CM/1 PC` per parcel).

## Cause

Staging balances store **fractional rolls** internally; parcel recipes consume `bubble_piece`. Only ship deduct and parcel estimate used `pool_yield_qty`. Issue UI, balances table, and damage report used roll units end-to-end.

## Fix

API converts sized bubble rows to **pc** for display (`issued × pool_yield_qty`). Damage accepts `qty_unit: pc` (default for sized bubble) and converts to fractional rolls via `snap_sixteenths(pieces / yield)`. DB unchanged — still roll ledger.

## Files

| Role | Path |
|------|------|
| Primary | `includes/fulfillment_packing.php` |
| API | `api/v1/fulfillment_packing.php` |
| SPA | `app/src/app/pages/fulfillment/PackingMaterialsStagingPage.tsx` |
| Client | `app/src/app/lib/fulfillmentPackingApi.ts` |

## Deploy

PHP + `cd app && npm run build` + hard refresh.

## Links

- **Spec:** [[specs/2026-06-17_fulfillment-packing-staging]]
- **Fragile edges:** [[003_Fragile_Edges#Ship deduct]]
