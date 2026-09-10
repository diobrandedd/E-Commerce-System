---
date: 2026-09-10
type: fix
area: dept-paragis
---

# Fix: Bag pool + pouch scan deduct

1. Settings `pt.grams_per_tea_bag` / `pt.tea_bags_per_pouch` (defaults 2 × 24).
2. Pouch/SKU scan for `pt` deducts bags via `dept_raw_deduct_bags_allow_negative` (warn + allow overdraft).
3. Hide WIP actions; bag pool progress strip on Paragis page; update fragile-edge/copy.
