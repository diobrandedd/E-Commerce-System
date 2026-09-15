---
date: 2026-09-15
type: fix
area: dept-raw-inventory
tags: [fix]
---

# Fix: Raw stock-in as total kilos (pt / brc / jar_cacao)

1. Set `stock_in_unit` to `kg` in `dept_product_catalog()` for `pt`, `brc`, `jar_cacao` only (`honey_comb` stays `bag`).
2. SPA labels: Quantity (kg), ₱/kg, RAW (kg), leaf waste, yield hints “1 kg → …”.
3. Keep API fields `bags_on_hand` / `dept_raw_stock_in_*` — presentation only; no DB migration; scan deduct math unchanged.
