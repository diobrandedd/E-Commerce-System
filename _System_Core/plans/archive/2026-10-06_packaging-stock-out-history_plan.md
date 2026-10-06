---
status: approved
spec: "[[specs/2026-10-06_packaging-stock-out-history]]"
created: 2026-10-06
approved_by: chat
---

# Implementation Plan: Packaging stock-out history

> Spec: [[specs/2026-10-06_packaging-stock-out-history]]

## Summary

Expose paginated packaging `use:` stock-out history from `stock_entries` via `materials.php`, and add an SPA history page linked from Packaging Materials.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]]
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | `packaging_stock_out_history` on `materials.php` |
| Includes | `material_packaging_stock_out_history` |
| DB | none |
| SPA | history page, route, permissions, nav link |

## Implementation steps

### Phase 1 — Backend

1. [ ] `material_packaging_stock_out_history` in `includes/material_inventory.php`
2. [ ] API action gated by `packaging.view`

### Phase 2 — Frontend

1. [ ] `fetchPackagingStockOutHistory` in `materials.ts`
2. [ ] `PackagingStockOutHistoryPage` + route/permissions
3. [ ] History link on Packaging Materials + sidebar

### Phase 3 — Cleanup

1. [ ] Verify + memory bank
2. [ ] Archive this plan

## Files to touch (expected)

| File | Action |
|------|--------|
| `includes/material_inventory.php` | modify |
| `api/v1/materials.php` | modify |
| `app/src/app/lib/materials.ts` | modify |
| `app/src/app/pages/inventory/PackagingStockOutHistoryPage.tsx` | create |
| `app/src/app/pages/inventory/PackagingMaterialsPage.tsx` | modify |
| `app/src/app/routes.ts` | modify |
| `app/src/app/lib/permissions.ts` | modify |
| `app/src/app/components/Sidebar.tsx` | modify |

## Test plan

- [ ] Stock out via scan → row appears on history with actor name
- [ ] `packaging.view` can open history; deduct-only cannot
- [ ] Search / date / person filters work; pagination works
- [ ] Mobile cards + desktop table

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-10-06
