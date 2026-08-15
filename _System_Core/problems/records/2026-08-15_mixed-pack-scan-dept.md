---
date: 2026-08-15
type: problem-record
status: fixed
area: pack-scan
tags: [problem]
---

# Problem: Pack scan could not deduct Paragis / BRC / Cacao / Comb

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Pack scan accepted honey bottle barcodes only. Mixed parcels (honey + pouches/jars) could not deduct dept finished stock at pack time; History had no product filter.

## Cause

`honey_bottle_pack_session_items` FKs pointed only at honey barcode/unit tables. Scan looked up honey codes exclusively; confirm never called `dept_inventory_deduct`.

## Fix

- Migration phase 60: companion table `honey_bottle_pack_session_dept_items`.
- Pack scan tries honey first, then dept pouch barcodes (pt / brc / jar_cacao / honey_comb).
- Confirm marks dept units `packed` and deducts finished `dept_inventory_rows`.
- Session payload unifies items with `kind`; History filter `product_key`.

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase60_pack_session_dept_items.sql` |
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` |
| SPA | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |
| SPA client | `app/src/app/lib/bottleBarcodes.ts` |
| Spec | [[specs/2026-08-15_mixed-pack-scan-dept]] |

## Deploy

- Run phase 60 SQL on target DB
- `cd app && npm run build` + hard refresh / regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Online parcels / ship]]
- **Related:** [[problems/records/2026-08-15_pack-scan-history]]
