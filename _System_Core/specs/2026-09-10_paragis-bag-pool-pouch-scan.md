---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [paragis, pt, bag-pool, pouch-scan, settings, bom]
---

# Paragis bag pool → pouch scan (skip WIP)

> Stock raw 1 kg bags into a **bag pool**. Scanning pouches into inventory credits finished goods and deducts leaf from the pool (default **48 g** / pouch). No tea-bag WIP stage. BOM factors editable in Settings.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[005_SPA_UX_Patterns]] · [[specs/2026-06-05_paragis-tea-bom-math]] · [[fixes/paragis-use-raw-bags]]

## Problem

Floor workflow still shows **Use raw bags → tea bags**, WIP waste, and inventorial copy that pouch scan does **not** deduct raw. Ops no longer uses WIP; they want bag stock-in + pouch scan with a visible pool / progress bar.

## Locked behavior (chat 2026-09-10)

1. **Hide** Paragis WIP actions: Use raw bags, WIP waste, and tea-bags→pouch produce (not used today).
2. **Stock in raw bags** adds to a **bag pool** (progress / remaining leaf or bags).
3. **Scan pouch to inventory** → +1 finished pouch **and** deduct leaf from pool using settings:  
   `grams_per_tea_bag × tea_bags_per_pouch` (defaults **2 × 24 = 48 g** from a **1000 g** bag).
4. If pool is insufficient → **warn**, but **allow** scan; pool may go **negative**.
5. **No** packaging materials deduct on pouch scan (sachets / pouch packaging ignored).
6. **Settings** (executive): editable `pt.grams_per_tea_bag`, `pt.tea_bags_per_pouch` (and display derived g/pouch); bag size stays **1000 g** unless we later add `pt.grams_per_bag`.

## Success criteria

- [ ] Paragis Do next no longer shows Use raw bags / WIP waste / WIP pouch produce.
- [ ] Bag pool progress visible on Paragis raw page; updates on stock-in and each pouch scan.
- [ ] Scan credits finished inventory and deducts configured g from pool; negative allowed with warning.
- [ ] Settings can change tea-bag grams and bags-per-pouch; next scans use new factors (existing pool grams unchanged until deducted).
- [ ] Guide / Do next copy no longer says “barcode stock-in does not deduct raw.”
- [ ] BRC / Jar Cacao / Honey Comb workflows unchanged.

## Non-goals

- Migrating or auto-clearing historical WIP tea-bag balances (optional cleanup later).
- Packaging `stocks.on_hand` deduct for PT sachets/pouches.
- Changing BRC/Cacao to the same pool model in this spec.

## Approval

- [ ] Behavior confirmed
- [ ] Shape brief confirmed
- [ ] Implementation plan approved
- Approved date: _
