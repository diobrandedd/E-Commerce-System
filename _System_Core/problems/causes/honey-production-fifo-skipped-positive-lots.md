---
date: 2026-09-30
type: cause
---

# Cause: FIFO skipped positive production lots before overdraw

`honey_production_fifo_deduct_gallons` filtered `COALESCE(is_active, 1) = 1` only. Gallons on inactive lots still appeared in the variant rollup (Available) but were invisible to FIFO, so bottle stock-in overdrew an active lot (often to negative) while sibling lots still held stock.

Even with all lots active, overdraw had no second-pass guard that refused to go negative while any `current_qty > 0` remained.
