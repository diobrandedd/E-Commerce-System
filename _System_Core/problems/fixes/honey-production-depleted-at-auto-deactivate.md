---
date: 2026-09-30
type: fix
---

# Fix: depleted_at + 1 hour lazy auto-deactivate

1. Phase 88 adds `honey_production_rows.depleted_at`.
2. Deduct / corrections stamp `depleted_at = NOW()` on first empty/overdraw; clear when qty > 0.
3. `honey_production_auto_deactivate_stale_empty` runs on Production list + FIFO: `is_active=0` when empty and `depleted_at` older than 1 hour.
4. Warehouse → production refill clears `depleted_at` and sets `is_active=1`.
5. Existing empty lots backfilled from `transferred_at` so long-empty rows deactivate on first sweep.
