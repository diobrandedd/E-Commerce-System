---
date: 2026-06-18
type: problem-record
status: fixed
area: fulfillment-packaging
tags: [problem, parcel-pack, cushion]
---

# Problem: Parcel tiers used roll columns where Excel specifies bought liter cushions

> Tiers showed `Cushion roll - main` for 1L honey, PT/BRC, and cacao rows; ops actually purchases customized cushion pieces (1L / 2L / 3L).

## Symptom

Parcel packaging tier preview and costing listed roll column cuts for liter-sized orders. Packaging Materials and spreadsheet "Cushion type (customize)" use purchased `Cushion piece - 1/2/3 liter` SKUs instead.

## Cause

→ [[causes/parcel-tier-roll-vs-bought-liter-cushion]]

## Fix

→ [[fixes/parcel-tier-bought-liter-cushion-pieces]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/fulfillment_pack_tier_seed.php` |
| Migration | `sql/migration_phase40_custom_cushion_pieces.sql` |
| Related | `sql/migration_phase39_cleanup_legacy_packaging.sql` |
| Seed tool | `tools/seed_fulfillment_pack_tiers.php` |

## Deploy

- Run `migration_phase40_custom_cushion_pieces.sql`, then `php tools/seed_fulfillment_pack_tiers.php`
- Stock-in purchased liter cushion pieces so tier WAC is non-zero

## Links

- **Spec:** [[specs/2026-06-17_fulfillment-packaging-recipes]]
- **Session:** [[specs/2026-06-18_costing-parcel-packaging-session]]

## AI rules (optional)

Liter honey / PT / BRC / cacao solo tiers → bought `Cushion piece - * liter` when Excel "customize" column has 1/2/3 LITER. Roll columns stay for gallon bulk and multi-liter honey combos that need main/divider rolls.
