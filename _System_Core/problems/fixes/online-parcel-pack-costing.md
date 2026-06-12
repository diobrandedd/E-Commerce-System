# Fix: Online parcel pack costing (tier-based)

**Shipped:** 2026-06-05  
**Spec:** [[specs/2026-06-05_online-parcel-pack-costing]]

## Problem

Manufacturing COGS (`product_formula`) cannot model parcel fulfillment packaging — same honey SKU appears in 1×, 2×, 3×1L parcels with different bubble-wrap counts; rolls have no fixed yield per parcel.

## Fix

Separate **parcel pack tier** layer:

| Layer | Tables / code |
|-------|----------------|
| Schema | `parcel_pack_tiers`, `parcel_pack_tier_lines` — `sql/migration_phase30_parcel_pack_costing.sql` |
| Resolver | `includes/parcel_pack_costing.php` — shape from parser, tier keys, min/typical/max cost bands |
| API | `api/v1/costing.php` — `parcel_tiers_list`, `parcel_tier_line_save`, `parcel_tier_line_delete`, `parcel_cost_preview`; `products_list&parcel=1` adds landed columns |
| UI | `CostingPage` toggle Product COGS \| Parcel packaging; `ParcelPackCostingPanel`, `CostingParcelMetrics` |

## Files

- `sql/migration_phase30_parcel_pack_costing.sql`
- `includes/parcel_pack_costing.php`
- `api/v1/costing.php`
- `app/src/app/lib/parcelPackCosting.ts`
- `app/src/app/components/costing/ParcelPackCostingPanel.tsx`
- `app/src/app/components/costing/CostingParcelMetrics.tsx`
- `app/src/app/pages/exec/CostingPage.tsx`
- `app/src/app/components/costing/CostingHoneyVariantGroups.tsx`
- `app/src/app/components/costing/CostingGuideModal.tsx`

## Out of scope (Phase 2)

Ship-time deduct of fulfillment packaging SKUs; CSR pack confirm override UI.
