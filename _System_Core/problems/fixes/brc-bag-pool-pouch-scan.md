---
date: 2026-09-10
type: fix
area: inventory
tags: [fix]
---

# Fix: BRC bag pool → pouch scan

Add `brc.grams_per_pouch` / `brc.grams_per_bag` Settings BOM. Snapshot `bag_pool` for BRC; unique/SKU scans call `dept_raw_deduct_bags_allow_negative`. Shared `DeptBagPoolStrip` progress UI; hide inventorial Produce as primary path.
