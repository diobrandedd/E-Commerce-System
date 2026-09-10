---
date: 2026-09-10
type: fix
area: inventory
tags: [fix]
---

# Fix: Jar Cacao bag pool → jar scan

Add `jar_cacao.grams_per_jar` / `jar_cacao.grams_per_bag` Settings BOM. Snapshot `bag_pool` for cacao; unique/SKU scans call `dept_raw_deduct_bags_allow_negative`. Shared `DeptBagPoolStrip` with jar labels; hide inventorial Produce.
