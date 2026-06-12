---
status: approved
created: 2026-06-05
area: online-parcel-packaging
tags: [parcel, costing, packaging, fulfillment, tiers]
---

# Online parcel pack costing (tier-based)

## Summary

Parcel fulfillment packaging (bubble wrap, cushions, courier pouch, foam) is **not** part of `product_formula` manufacturing COGS. It uses **composition-based tiers** resolved from order `Item/s` content, with **min / typical / max** qty for variable lines (bubble wrap).

**Phase 1:** Costing UI + tier editor + preview calculator. **No** auto-deduct on ship (Phase 2).

## Families

| Family | Products | Courier pouch |
|--------|----------|---------------|
| `honey` | Honey bottles (by 1L count v1) | **No** |
| `dept_pt_brc` | Paragis Tea, Black Rice Coffee | Yes |
| `jar_cacao` | Jar Cacao | Yes |

## v1 tier rules (seeded)

### Honey (1L count only in v1)

| tier_key | Trigger | Materials |
|----------|---------|-----------|
| `honey_1x_1L` | 1× 1L | bubble wrap 2–3 (typ 2.5), cushion standard ×1 |
| `honey_2x_1L` | 2× 1L | bubble wrap 3–4 (typ 3.5), cushion medium ×1, small separator ×1 |
| `honey_3x_1L` | 3× 1L | bubble wrap 3–4 (typ 3.5), cushion large ×1, small separator ×2 |
| `honey_250_350_solo` | TBD | placeholder — inactive until researched |

### Dept (PT + BRC)

| tier_key | Trigger | Materials |
|----------|---------|-----------|
| `dept_1_2_units` | 1–2 units | cushion standard ×1, courier pouch ×1 |
| `dept_3_4_units` | 3–4 units | cushion medium ×1, courier pouch ×1 |

### Jar Cacao

| tier_key | Trigger | Materials |
|----------|---------|-----------|
| `cacao_1_2_jars` | 1–2 jars | foam wrap ×1, cushion standard ×1, courier pouch ×1 |
| `cacao_3_4_jars` | 3–4 jars | foam wrap 3–4 (typ 3.5), cushion medium ×1, courier pouch ×1 |

## Mixed parcels

**Sum** applicable family tier costs (honey tier from 1L count + dept tier from PT/BRC qty + cacao tier from jar qty).

## Costing math

- Countable lines: `qty × stock WAC` (`current_price` from Packaging Materials lots).
- Variable lines: `qty_min`, `qty_typical`, `qty_max` → display ₱ band + typical.
- Rolls: no required `pool_yield_qty`; optional `typical_parcels_per_roll` on stock for reference only.

## Fulfillment packaging SKUs

Seeded under honey dept, `category = packaging`, `production_deduct = 0` (parcel costing only in Phase 1):

- Parcel bubble wrap
- Parcel cushion — standard / medium / large / small separator
- Courier pouch bag
- Parcel insulation foam wrap

## Non-goals (Phase 1)

- Deduct packaging on parcel ship
- CSR pack override UI
- 250/350ml solo honey rules (TBD tier)

## Links

- [[specs/2026-06-05_packaging-production-deduct]]
- Phase 2: ship deduct in `honey_parcel_apply_deduct`
