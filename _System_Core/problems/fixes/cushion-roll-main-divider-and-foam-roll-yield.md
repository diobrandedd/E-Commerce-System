---
type: fix
tags: [fix, fulfillment-packaging, cushion, foam, migration]
---

# Fix: Cushion main/divider rolls + LAD piece + foam roll yields

## What changed

- Added phase 37 migration to consolidate legacy cushion roll variants into:
  - `Cushion roll - main`
  - `Cushion roll - divider`
- Kept `Cushion piece - LAD` active for 350ml flows; `half of Lad` stays `0.5 pcs`
- Converted insulation foam stock model to roll SKUs:
  - `Insulation foam roll - 9x12`
  - `Insulation foam roll - 12.5x12`
- Extended fulfillment deduct/costing math to support `column` and `foam_piece` units (same roll-yield conversion pattern as `bubble_piece`)
- Updated tier seed to use explicit column counts from spreadsheet rows and foam roll piece usage
- Enabled optional pool-yield update during packaging stock-in for HR/executive

## Key files

- `sql/migration_phase37_cushion_roll_gal_lad.sql`
- `includes/fulfillment_pack_tier_seed.php`
- `includes/material_inventory.php`
- `includes/fulfillment_packing.php`
- `includes/parcel_pack_costing.php`
- `api/v1/materials.php`
- `app/src/app/components/inventory/PackagingStockInLotModal.tsx`

## Deploy

1. Run `sql/migration_phase37_cushion_roll_gal_lad.sql`
2. Run `php tools/seed_fulfillment_pack_tiers.php`
3. HR/Executive stock-in cushion/foam rolls and set yield values where needed
