---
status: approved
created: 2026-06-17
area: fulfillment-packaging
tags: [packaging, parcel, tiers, inventory, deduct]
---

# Fulfillment packaging recipes (spreadsheet alignment)

## Summary

Parcel fulfillment packaging uses **exact recipes** from ops spreadsheet — not generic std/med/lg cushions or summed family tiers. HR stocks **bubble rolls**, **cushion rolls + LAD piece cushions**, **pouches**, **foam rolls**, and **cardboard**. On parcel **ship**, fulfillment SKUs deduct idempotently (after finished-goods deduct).

## Layers

| Layer | Examples | Deduct when |
|-------|----------|-------------|
| Production packaging | bottles, pouches, tea bag roll, seals | bottling / produce |
| Fulfillment packaging | bubble rolls, cushion pieces/rolls, pouches, foam, cardboard | parcel ship |

## SKU catalog

### Bubble wrap rolls (`unit=roll`, `production_deduct=0`, pool_yield = pieces/roll)

| SKU | pool_yield_qty |
|-----|----------------|
| Bubble wrap roll - 13 cm | 264 |
| Bubble wrap roll - 17 cm | 204 |
| Bubble wrap roll - 22 cm | 136 |

Tier lines store **bubble pieces**; ship deduct converts `pieces / pool_yield_qty` (fractional roll).

### Cushion piece SKU (`unit=pcs`)

`Cushion piece - LAD` (use **0.5 pcs** when ops cuts a LAD in half for 250ml / 350ml solo) and `Cushion piece - honey comb`.

### Cushion rolls (`unit=roll`, fractional on-hand in 1/16 steps)

`Cushion roll - main` (GAL) and `Cushion roll - divider` (package separator). Recipes store **column usage** and deduct `columns / pool_yield_qty`.

### Other fulfillment

- `Courier pouch - medium` / `large` / `x-large`
- `Insulation foam roll - 9x12` / `12.5x12` (recipes use `foam_piece`)
- `Cardboard box - whole` (HALF = 0.5, fractions in 1/16 grid e.g. 7/16 = 1 1/6)

Phase-30 generic SKUs (`Parcel bubble wrap`, `Parcel cushion - standard`, …) are **deactivated**.

## Matcher priority

1. **Bulk exact** — `honey_350ml ∈ {18,21,24,36}` or `honey_250ml ∈ {21,24,42}` (no other products)
2. **Honey comb** — `honey_comb_count >= 1` (solo comb parcel)
3. **Combo recipes** — dept + honey / jar + honey (explicit rows)
4. **Single-family** — honey-only, dept-only, cacao-only

**No additive sum** of family tiers. Unmatched shape → warning + block ship.

## Order shape (parser output)

Extended fields: `honey_1L_count`, `honey_250ml`, `honey_350ml`, `honey_gallon_count`, `honey_comb_count`, `dept_pt`, `dept_brc`, `jar_units`.

## Deduct math

- **pcs** — integer deduct (fractional allowed for **LAD** when recipe uses half-piece, e.g. 0.5)
- **bubble/foam/column roll units** (`bubble_piece`, `foam_piece`, `column`) — `qty / pool_yield_qty`, then snap in **1/16** roll increments
- **cardboard** — deduct in **1/16** increments
- **Insufficient stock** — block ship with clear error
- **Idempotent** — skip if `online_parcel_fulfillment_deduct_lines` already has rows for parcel

## Links

- Supersedes v1 tiers in [[specs/2026-06-05_online-parcel-pack-costing]]
- [[fixes/packaging-inventory-roles]]
- Seed: `includes/fulfillment_pack_tier_seed.php` + `sql/migration_phase33_fulfillment_packaging.sql`
