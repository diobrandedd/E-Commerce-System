# Online parcel pack costing (tier-based)

**Date:** 2026-06-05  
**Status:** Shipped (Phase 1 — costing only)

## Problem

Product formulas model per-unit manufacturing BOM, not per-parcel fulfillment packaging. Bubble wrap usage varies by order composition (1L count, dept units, jar count). Duplicating parcel lines into every honey SKU would be wrong.

## Cause

Single `product_formula` rollup assumes fixed qty per sellable unit. Parcel materials are keyed by **order shape**, not product SKU.

## Fix

- DB tiers + lines seeded for honey / PT+BRC / jar cacao families
- `parcel_pack_costing.php` resolves tiers from `honey_parcel_parse_items_string`, sums mixed parcels, estimates min/typical/max from packaging WAC
- Costing UI second tab for tier edit + preview; landed cost columns on product list

**Fix note:** [[fixes/online-parcel-pack-costing]]  
**Spec:** [[specs/2026-06-05_online-parcel-pack-costing]]

## Files

See fix note file list.
