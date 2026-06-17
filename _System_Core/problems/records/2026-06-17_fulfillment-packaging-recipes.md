# Problem: Parcel fulfillment packaging out of sync with ops spreadsheet

## Cause

Phase-30 tiers used generic cushions/bubble and additive family matching. No ship-time deduct for fulfillment SKUs.

## Fix

[[fixes/fulfillment-packaging-recipes]] — catalog migration phase 33, tier seed, combo matcher, fractional roll deduct on ship.

## Files

- `includes/parcel_pack_costing.php`
- `includes/fulfillment_pack_tier_seed.php`
- `sql/migration_phase33_fulfillment_packaging.sql`
- `app/src/app/pages/inventory/PackagingMaterialsPage.tsx`
