---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [brc, black-rice, bag-pool, pouch-scan, settings, bom]
---

# Black Rice bag pool → pouch scan

> Stock raw 1 kg bags into a **bag pool**. Scanning BRC pouches into inventory credits finished goods and deducts leaf from the pool (default **100 g** / pouch). Progress bar like Paragis. BOM editable in Settings.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[005_SPA_UX_Patterns]] · [[specs/2026-09-10_paragis-bag-pool-pouch-scan]] · [[specs/2026-06-11_brc-1kg-bom]]

## Problem

BRC still uses **Produce pouches** (formula deduct raw + packaging). Ops wants the same bag-pool model as Paragis: stock bags → scan pouches → pool drops with a progress bar.

## Locked behavior (chat 2026-09-10)

1. **Hide** BRC inventorial **Produce pouches** / daily produce-as-primary path for floor ops (same spirit as hiding Paragis WIP). Keep bag waste + stock-in.
2. **Stock in raw bags** fills the **bag pool** (progress / remaining grams).
3. **Scan pouch to inventory** (unique or SKU) → +1 finished pouch **and** deduct **grams_per_pouch** from the pool (default **100 g** from a **1000 g** bag → **0.1 bags**).
4. If pool is insufficient → **warn**, but **allow** scan; pool may go **negative**.
5. **No** packaging materials deduct on pouch scan (`BRC pouch packaging` ignored on scan).
6. **Settings** (executive):
   - `brc.grams_per_pouch` — default **100** (deduct per scan)
   - `brc.grams_per_bag` — default **1000** (supplier bag size / capacity math)
7. Reuse / generalize bag-pool UI (progress strip) for BRC; do not leave Paragis-only hardcoding.
8. Jar Cacao / Honey Comb unchanged in this spec.

## Success criteria

- [ ] BRC raw page shows bag-pool progress; updates on stock-in and each pouch/SKU scan.
- [ ] Scan credits finished inventory and deducts configured g from pool; negative allowed with warning.
- [ ] Settings can change grams/pouch and grams/bag; next scans use new factors.
- [ ] Produce-pouches inventorial CTA hidden or demoted so Do next matches bag-pool flow.
- [ ] Paragis pool behavior unchanged; `stock_entries.qty` stays DECIMAL(14,6) so 0.1 bags stores cleanly.
- [ ] Guide / subtitle / Do next copy match bag-pool language.

## Non-goals

- Auto-migrating historical `produce_brc` log rows.
- Packaging `stocks.on_hand` deduct for BRC pouch packaging on scan.
- Changing Jar Cacao to bag-pool in this spec.

## Approval

- [x] Behavior confirmed
- [x] Implementation plan approved
- Approved date: 2026-09-10
