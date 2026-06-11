---
date: 2026-06-11
type: problem-record
status: fixed
area: inventory
tags: [problem]
---

# Problem: Packaging unit cost kept shifting on repeat stock-in

> Same lot (₱500 / 100 pcs) showed unit cost moving (150 → 77.50 → 53.33) instead of staying at ₱5.00/pc.

## Symptom

User stocked in identical lots; table unit cost did not match modal preview (₱5.00/pc). Bottle Caps showed ₱53.33 at 300 on hand.

## Cause

Legacy `set_values` had set `current_price = 150` on the row. WAC blended that stale manual price with new lot unit costs instead of deriving cost from purchase lots only.

## Fix

- `material_packaging_pool_unit_cost_from_lots()` — sum lot purchase totals, value pre-lot on-hand at average lot unit
- Stock-in applies derived price after inserting the lot entry
- Costing price update runs before duplicate `current_price` write

## Files

| Role | Path |
|------|------|
| Backend | `includes/material_inventory.php` |
| SPA | `PackagingStockInLotModal.tsx` |

## Links

- [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- [[problems/records/2026-06-05_packaging-lot-stock-in]]
