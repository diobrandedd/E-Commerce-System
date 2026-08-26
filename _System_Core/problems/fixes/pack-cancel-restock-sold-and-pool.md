---
date: 2026-08-26
type: fix
area: deliveries-cancelled-restock
tags: [fix]
---

# Fix: Accept sold barcodes and credit by pool

1. Treat `packed`, `shipped`, and `sold` as restorable.
2. On confirm, credit packaging/finished or RTS sellable from each unit’s `pool`; for honey `sold`, write qty −1 walk-in sale log so Sales counts stay honest.
3. Expose `unit_pool` / `stock_label` on session items; update Cancelled page + guide copy for POS walk-in cancel.
