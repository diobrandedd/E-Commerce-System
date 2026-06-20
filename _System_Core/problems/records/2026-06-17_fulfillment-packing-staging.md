# Problem: Ship deducted warehouse instead of packing staging

## Symptom
Fulfillment packaging ship scan reduced `stocks.on_hand` directly; no visibility of materials issued to the packing area.

## Cause
Phase 33 `parcel_fulfillment_apply_deduct` called `material_packaging_deduct_fulfillment_line` on warehouse on-hand.

## Fix
Phase 34: `fulfillment_packing_balances` + issue transfer; `fulfillment_packing_consume_line` on ship. PM issues via `/fulfillment/packing-materials`. HR price-only (`packaging_price_lot`). On-hold + re-waybill without revert.

## Files
- `sql/migration_phase34_fulfillment_packing_staging.sql`
- `includes/fulfillment_packing.php`, `includes/material_inventory.php`
- `api/v1/fulfillment_packing.php`, `api/v1/deliveries.php`
- `app/src/app/pages/fulfillment/PackingMaterialsStagingPage.tsx`

## Spec
[[specs/2026-06-17_fulfillment-packing-staging]]
