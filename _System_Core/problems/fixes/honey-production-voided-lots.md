---
date: 2026-10-01
type: fix
---

# Fix: Soft-void production lots (manual + 2-day auto)

1. Phase 89: `voided_at` / `voided_by` on `honey_production_rows`.
2. Main list excludes voided; `voided_count` per variant.
3. Executive **Void** on empty rows; **Voided (N)** opens per-variant modal with **Unvoid**.
4. Auto-void when `current_qty <= 0`, inactive, and `depleted_at` older than 2 days (lazy on list/FIFO).
5. Warehouse refill / stocked reactivate clears void flags.
