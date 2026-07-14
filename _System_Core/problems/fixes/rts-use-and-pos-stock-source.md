---
date: 2026-07-14
type: fix
area: rts
---

# Fix: Use RTS + POS stock_source

1. `rts_pool_use_log` with `used_where` = `pos` | `packaging`.
2. Use RTS on RTS page logs Packaging; POS/AR with `stock_source=rts` logs POS and deducts `rts_inventory_rows`.
3. Non-RTS POS keeps packaging / dept FG deduct.
4. Nested-safe deduct (ownsTransaction) for POS checkout wrappers.
