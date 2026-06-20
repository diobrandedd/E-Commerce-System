---
date: 2026-06-05
type: problem-record
status: fixed
area: parcel-pack-costing
tags: [problem, costing, feature]
---

# Problem: Online parcel pack costing (tier-based) — feature ship

> Phase 1 feature record. **Post-ship bugs:** [[records/2026-06-05_parcel-pack-ship-bugs]] ⭐

## Symptom

Manufacturing `product_formula` cannot model per-parcel fulfillment packaging (bubble wrap varies by 1L count; same SKU in different parcel shapes).

## Cause

COGS rollup is per sellable unit; parcel materials are keyed by **order composition**, not product SKU.

→ See feature design in [[specs/2026-06-05_online-parcel-pack-costing]]

## Fix

→ [[fixes/online-parcel-pack-costing]]

## Files

| Role | Path |
|------|------|
| Spec | [[specs/2026-06-05_online-parcel-pack-costing]] |
| Migrations | `sql/migration_phase30_*.sql`, `sql/migration_phase31_*.sql` |
| Backend | `includes/parcel_pack_costing.php`, `api/v1/costing.php` |
| SPA | `CostingPage.tsx`, `ParcelPackTiersModal.tsx` |

## Links

- **Ship bugs rollup:** [[records/2026-06-05_parcel-pack-ship-bugs]]
- **Fragile edges:** [[003_Fragile_Edges]] (Online order parcels + parcel pack costing bullets)
- **Patterns:** [[005_SPA_UX_Patterns#Product Costing page]]
