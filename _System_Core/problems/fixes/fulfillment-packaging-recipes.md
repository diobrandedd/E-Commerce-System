---
type: fix
tags: [fix, packaging, fulfillment, parcel, tiers, deduct]
---

# Fix: Fulfillment packaging recipes + ship deduct

## Problem

Parcel fulfillment used 7 generic SKUs and summed family tiers. Ops spreadsheet defines exact bubble widths, cushion pieces/rolls, pouch sizes, and combo recipes.

## Fix

- New SKUs: bubble rolls (13/17/22 cm yields), cushion pieces (LAD, liter sizes, honey comb), column rolls, pouches, foam, cardboard
- `fulfillment_pack_tier_seed.php` — 37 recipe tiers, combo-first matcher in `parcel_pack_costing.php`
- Ship: `parcel_fulfillment_apply_deduct` + `online_parcel_fulfillment_deduct_lines`; revert on cancel
- SPA: Packaging **Fulfillment** filter; Costing parcel preview shows single `tier_key`

## Files

- `sql/migration_phase33_fulfillment_packaging.sql`
- `includes/fulfillment_pack_tier_seed.php`, `tools/seed_fulfillment_pack_tiers.php`
- `includes/parcel_pack_costing.php`, `includes/material_inventory.php`, `includes/honey_parcel_inventory.php`
- `_System_Core/specs/2026-06-17_fulfillment-packaging-recipes.md`

## Deploy

1. `migration_phase33_fulfillment_packaging.sql`
2. `php tools/seed_fulfillment_pack_tiers.php`
