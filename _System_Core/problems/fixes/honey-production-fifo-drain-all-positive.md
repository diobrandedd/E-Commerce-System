---
date: 2026-09-30
type: fix
---

# Fix: Drain all positive production lots before overdraw

1. `honey_production_fifo_deduct_gallons` locks all variant lots and takes oldest-first from every row with `current_qty > 0` (including inactive).
2. Before overdraw, a second `FOR UPDATE` pass re-reads any remaining positive lots and drains them.
3. Overdraw only after the variant has no positive gallons left; target is the last emptied lot (else newest active).
4. `honey_production_variant_summary` sorts lots with stock first (then overdrawn, then by stock-out date).
