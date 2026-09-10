---
date: 2026-09-10
type: fix
area: inventory
tags: [fix]
---

# Fix: Widen stock_entries.qty to DECIMAL(14,6)

Run `sql/migration_phase78_stock_entries_qty_precision.sql` so bag-fraction pouch deducts (and any other sub-centi bag movements) persist without rounding to two decimals. Keep this column at ≥6 fractional digits.
