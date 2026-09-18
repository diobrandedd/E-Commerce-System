---
date: 2026-09-18
type: fix
area: honey-production
tags: [fix]
---

# Fix: Clear initial when empty; fresh max on next stock-out

1. `honey_production_deduct_gallons` sets `initial_qty = 0` when remaining `current_qty ≤ 0`.
2. Lot refill: if prior current ≤ 0, new `initial_qty` = gallons just stocked (fresh cycle); mid-cycle still accumulates.
3. Phase 80 zeros existing empty/overdrawn lots’ stale initials.
