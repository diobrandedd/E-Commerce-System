---
date: 2026-09-10
type: fix
---

# Fix: Per-product bag_pool_deduct Settings toggles

1. Keys `pt.bag_pool_deduct`, `brc.bag_pool_deduct`, `jar_cacao.bag_pool_deduct` (default on) in BOM get/save.
2. Pouch + SKU stock-in skip raw deduct when off; response still returns pool snapshot.
3. Settings switches on each BOM card; bag-pool strip shows “Scan deduct off” note when disabled.
