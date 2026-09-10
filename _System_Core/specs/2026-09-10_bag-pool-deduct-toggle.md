---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [bag-pool, settings, pt, brc, jar-cacao, toggle]
---

# Bag-pool deduct Settings toggles (PT / BRC / Cacao)

> Executive can turn **raw bag-pool deduct on finished stock-in** on or off per product. When off, pouch/jar scans still credit finished inventory but do not deduct raw bags.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[plans/active/2026-09-10_bag-pool-deduct-toggle_plan]]

## Locked behavior (chat 2026-09-10)

1. Three toggles: `pt.bag_pool_deduct`, `brc.bag_pool_deduct`, `jar_cacao.bag_pool_deduct` (default **on**).
2. Off → finished stock-in succeeds; no `dept_raw_deduct_bags_allow_negative`.
3. Bag pool strip stays visible; note when deduct is off.
4. Do not restore inventorial Produce when off.
5. Honey Comb / honey unchanged.

## Success criteria

- [x] Settings BOM cards expose deduct switch for PT / BRC / Cacao.
- [x] Unique + SKU stock-in skip raw deduct when toggle off.
- [x] Raw detail exposes `bag_pool_deduct_enabled`; strip shows off note.
- [x] Default on preserves current behavior.

## Non-goals

- Master all-products toggle
- Changing BOM gram math
- Honey Comb
