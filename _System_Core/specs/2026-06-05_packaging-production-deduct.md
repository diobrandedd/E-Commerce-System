---
status: approved
created: 2026-06-05
area: packaging-inventory
tags: [packaging, costing, production, pool-yield, bootstrap]
---

# Packaging production deduct + pool yield bootstrap

## Summary

Packaging stocks split into **per-unit** (`production_deduct = 1`) and **pool-only** (`production_deduct = 0`). Pool items use **`pool_yield_qty`** rough estimates for costing: `line_cost = (qty_per_unit / pool_yield_qty) × roll_WAC`. Migration seeds full catalog, formulas, and 12 honey costing SKUs.

## Flags

| Column | Per-unit | Pool-only |
|--------|----------|-----------|
| `production_deduct` | 1 | 0 |
| `pool_yield_qty` | NULL | e.g. 10000 |
| `pool_yield_unit` | NULL | `tea_bag`, `bottle`, `jar` |

## Pool on-hand unit

Pool SKUs use **`unit = roll`** (or `roll` for seals/foil). On-hand is **whole rolls** — stock-in 1 roll adds 1 to `on_hand`. Production **does not** deduct pool items; fractional `0.02` entries were from the old auto-deduct bug.

## Default yields (editable on Packaging Materials)

| Stock | `pool_yield_unit` | `pool_yield_qty` |
|-------|-------------------|------------------|
| `PT tea bag paper roll` | tea_bag | 10,000 |
| `Paragis Tea Bag Yarn` | tea_bag | 10,000 |
| `Honey induction seal` | bottle | 10,000 |
| `Honey cap seal` | bottle | 10,000 |
| `Jar Cacao foil` | jar | 8,000 |

## Formula qty (per sellable unit)

**Paragis:** leaf 0.0096; pouch 1; yarn 24; paper roll 24 (tea bags consumed per pouch).

**BRC:** raw 0.1; pouch 1.

**Jar:** raw 0.22; jar 1; sticker 1; foil 1.

**Honey (×12):** raw liters by size; bottle 1 (size-specific SKU); sticker 1; seals 1 each (pool).

## Production deduct

- PT Make tea bags: leaf only.
- PT Pouch: pouch only.
- BRC/Jar produce: per-unit packaging + raw only.
- Honey bottling: size bottle + sticker; seals pool-only.

## Honey sizes

`250ml`, `350ml`, `liter` (1L), `gallon` × variants `light`, `amber`, `dark`.

## Honey bottle SKU names (ASCII)

Use **ASCII hyphen** only — avoids Windows/MySQL encoding duplicates:

- `Honey bottle w/ cap - 250ml`
- `Honey bottle w/ cap - 350ml`
- `Honey bottle w/ cap - 1L`
- `Honey bottle w/ cap - gallon`

Cleanup: `migration_phase28_honey_bottle_dedup.sql` if `???` or em-dash duplicates exist.
