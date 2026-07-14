--
status: approved
spec: "[[specs/2026-07-14_monthly-inventory-executive]]"
created: 2026-07-14
approved_by: chat (user confirmed plan option 1 + execute)
---

# Implementation Plan: Monthly inventory executive

> Spec: [[specs/2026-07-14_monthly-inventory-executive]]

## Summary

Executive-only Monthly Inventory SPA page with domain create/set and Edit Logs tab; backend gaps for raw-bag set, RTS set, honey seed rows; unify writes into `inventory_edit_log` (+ optional `source` column).

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | honey.php, materials.php, rts.php actions |
| Includes | honey_inventory, dept_inventory, material_inventory, rts_inventory_sync |
| DB | phase52 `inventory_edit_log.source` |
| SPA | MonthlyInventoryPage, route, sidebar |

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-07-14
