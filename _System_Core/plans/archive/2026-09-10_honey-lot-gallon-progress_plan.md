---
status: shipped
spec: "[[specs/2026-09-10_honey-lot-gallon-progress]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Honey lot gallon progress + production overdraw

> Spec: [[specs/2026-09-10_honey-lot-gallon-progress]]

## Summary

Add `HoneyLotGallonProgress` to Production and Warehouse lot rows. Change `honey_production_deduct_gallons` / bottle stock-in callers to allow negative gallons with a warning payload; surface warn in the production scan panel. Keep warehouse stock-out blocking.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| Includes | `honey_production_deduct_gallons`, bottle stock-in callers — allow negative + warn |
| API | `bottle_barcodes.php` scan_stock_in passes warn fields |
| DB | none |
| SPA | Progress component; Production + Warehouse pages; scan panel; honey status helper |

## Implementation steps

### Phase 1 — Backend

1. [ ] Deduct without `current_qty >= ?`; return remaining including negative
2. [ ] Callers: warn instead of throw when short; include `production_overdrawn` / `warning`

### Phase 2 — Frontend

1. [ ] `HoneyLotGallonProgress` + overdrawn status on badge
2. [ ] Wire Production + Warehouse; keep depleted lots selectable for continued scan
3. [ ] Show warn on scan success when overdrawn

### Phase 3 — Cleanup

1. [ ] Playwright + verify
2. [ ] Memory bank + archive plan

## Files to touch (expected)

| File | Action |
|------|--------|
| `includes/honey_inventory.php` | modify |
| `app/src/app/lib/honey.ts` | modify |
| `app/src/app/lib/bottleBarcodes.ts` | modify types |
| `app/src/app/components/honey/HoneyLotGallonProgress.tsx` | create |
| `app/src/app/components/honey/HoneyStockStatusBadge.tsx` | modify |
| `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` | modify |
| `app/src/app/pages/honey/HoneyProductionPage.tsx` | modify |
| `app/src/app/pages/honey/HoneyWarehousePage.tsx` | modify |
| `tests/inventory/honey-lot-progress.spec.ts` | create |

## Test plan

- [ ] Mocked Production/Warehouse lists show progress fill
- [ ] Overdrawn row shows amber copy
- [ ] verify.ps1 PASS
