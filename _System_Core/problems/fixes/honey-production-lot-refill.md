---
date: 2026-09-16
type: fix
area: honey-production
tags: [fix]
---

# Fix: Cross-day production lot refill

1. Merge key = supplier + code + arrival + variant (no stock-out date).
2. Upsert finds existing lot (prefer overdrawn), adds gallons, updates `stock_out_date` to latest.
3. Folds leftover day-split duplicates when safe (packaging uniqueness check).
4. Consolidate tool groups across days; UI copy says later transfers refill the lot.
