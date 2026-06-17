# Problem: Cushion roll/LAD piece rules and foam roll usage were inconsistent

## Cause

[[causes/cushion-lad-roll-piece-model-mismatch]]

## Fix

[[fixes/cushion-roll-main-divider-and-foam-roll-yield]]

## Files

- `sql/migration_phase37_cushion_roll_gal_lad.sql`
- `includes/fulfillment_pack_tier_seed.php`
- `includes/material_inventory.php`
- `includes/fulfillment_packing.php`
- `includes/parcel_pack_costing.php`
- `api/v1/materials.php`
- `app/src/app/components/inventory/PackagingStockInLotModal.tsx`
- `app/src/app/pages/inventory/PackagingMaterialsPage.tsx`
- `app/src/app/pages/fulfillment/PackingMaterialsStagingPage.tsx`
- `_System_Core/specs/2026-06-17_fulfillment-packaging-recipes.md`
