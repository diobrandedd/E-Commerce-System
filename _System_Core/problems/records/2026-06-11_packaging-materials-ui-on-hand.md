---
date: 2026-06-11
type: problem-record
status: fixed
area: inventory
tags: [problem]
---

# Problem: Packaging Materials tab needed detail UX and role-split on-hand edits

> Clickable rows with activity history; executive direct count edit; HR requests via Inventory → Requests.

## Symptom

Flat table with only Stock in link; no detail view; no executive count correction after lot stock-in; HR could stock in lots but not request on-hand fixes separately.

## Fix

→ [[fixes/packaging-inventory-scroll-and-detail-ux]] (scroll regions, detail modal, HR nav — **canonical pattern**)

- Redesigned [`PackagingMaterialsPage`](../../../app/src/app/pages/inventory/PackagingMaterialsPage.tsx) — summary cards, clickable rows, **scrollable table body**
- [`PackagingMaterialDetailModal`](../../../app/src/app/components/inventory/PackagingMaterialDetailModal.tsx) — unit cost, pool, **capped scroll** recent entries, actions
- Executive `set_on_hand` API; HR `set_on_hand` requests (`migration_phase21_packaging_on_hand_requests.sql`)
- HR Requests page: packaging count request button; honey corrections hidden for HR
- HR sidebar: **Requests** top-level (outside Inventory)

## Files

| Role | Path |
|------|------|
| Backend | `includes/material_inventory.php`, `api/v1/materials.php`, `sql/migration_phase21_packaging_on_hand_requests.sql` |
| SPA | `PackagingMaterialsPage.tsx`, `PackagingMaterialDetailModal.tsx`, `PackagingOnHandSetModal.tsx`, `PackagingOnHandRequestModal.tsx`, `InventoryRequestsPage.tsx`, `materials.ts` |

## Deploy

- Run `migration_phase21_packaging_on_hand_requests.sql`
- `cd app && npm run build`

## Links

- [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- [[005_SPA_UX_Patterns#Packaging and materials inventory pages]]
- [[records/2026-06-11_packaging-ui-scroll-normalization]]
