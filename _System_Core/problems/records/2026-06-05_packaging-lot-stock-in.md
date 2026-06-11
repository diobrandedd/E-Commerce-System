---
date: 2026-06-05
type: problem-record
status: fixed
area: inventory
tags: [problem]
---

# Problem: Packaging stock-in used absolute price/qty instead of purchase lots

> HR enters total lot price and quantity received; system should derive ₱/piece, add to pool, and update WAC unit cost for costing.

## Symptom

`set_values` replaced absolute `current_price` and `on_hand` — wrong model for purchases. Ops enter total invoice amount (e.g. ₱500 for 500 bottles), not per-piece price. Quantity should accumulate in the pool, not reset.

## Cause

First packaging price/qty ship used absolute set pattern from inventory arrangement; did not match purchase-lot workflow or weighted-average costing when multiple receipts exist.

## Fix

- `material_packaging_stock_in_lot()` — lot unit = total ÷ qty; `on_hand += lot_qty`; WAC for `current_price` when pool > 0
- `materials.php?action=stock_in_lot` (executive, HR); removed `set_values` API
- [`PackagingStockInLotModal`](../../../app/src/app/components/inventory/PackagingStockInLotModal.tsx) with live unit-cost preview
- [`PackagingMaterialsPage`](../../../app/src/app/pages/inventory/PackagingMaterialsPage.tsx) — Stock in UX, unit cost + pool columns
- Costing Add formula line helper text: qty is per sellable unit; picker still shows WAC `current_price`

## Files

| Role | Path |
|------|------|
| Backend | `includes/material_inventory.php`, `api/v1/materials.php` |
| SPA | `PackagingStockInLotModal.tsx`, `PackagingMaterialsPage.tsx`, `CostingPage.tsx`, `materials.ts` |

## Links

- [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- [[plans/archive/2026-06-05_packaging-lot-stock-in_plan]]

## AI rules

Do not store lot total in `current_price` — costing formulas multiply `qty_per_unit × current_price` per piece. Use stock-in lot + WAC only.
