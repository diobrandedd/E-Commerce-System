---
date: 2026-06-05
type: problem-record
status: fixed
area: inventory
tags: [problem]
---

# Problem: Packaging materials lacked price/qty on inventory page

> Executive and HR needed to set unit price and on-hand; packaging stocks must feed product costing formulas.

## Symptom

Packaging Materials page showed on-hand only. Adjust used add/deduct deltas and included prod_manager. `current_price` was not visible or editable on that page. Formula picker did not group packaging stocks.

## Cause

Phase 20 inventory arrangement used generic material adjust for packaging and supplies; costing price lived only on Costing → Stocks (executive).

## Fix

- `material_packaging_set_values()` + `materials.php?action=set_values` (executive, HR)
- Dedicated [`PackagingMaterialsPage`](../../../app/src/app/pages/inventory/PackagingMaterialsPage.tsx) with price + qty columns
- [`PackagingSetValuesModal`](../../../app/src/app/components/inventory/PackagingSetValuesModal.tsx) for absolute values
- Costing `stocks_list` returns `category`; Add formula line **Packaging materials** optgroup
- Supplies unchanged (prod_manager request workflow)

## Files

| Role | Path |
|------|------|
| Backend | `includes/material_inventory.php`, `api/v1/materials.php`, `api/v1/costing.php` |
| SPA | `PackagingMaterialsPage.tsx`, `PackagingSetValuesModal.tsx`, `CostingPage.tsx`, `materials.ts` |

## Links

- [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- [[plans/archive/2026-06-05_packaging-price-qty_plan]]
