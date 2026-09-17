---
date: 2026-09-17
type: fix
area: honey-packaging
tags: [fix]
---

# Fix: Variant POOL rows + FIFO gallon deduct

1. Sentinel packaging rows per variant (`batch_no=POOL`, `bottling_date=2000-01-01`).
2. Phase 79 folds legacy lot on-hand into pools, zeros lot `current_*` (keep rows for history).
3. `scan_stock_in` credits pool + FIFO-deducts production gallons by variant; reject `production_row_id`.
4. SPA: Packaging = three expandable cards; Production = three expandable variant rows; scan panel = barcode + qty only.
