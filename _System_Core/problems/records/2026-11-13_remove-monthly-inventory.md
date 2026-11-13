---
date: 2026-11-13
type: problem-record
status: fixed
area: inventory
tags: [problem, monthly-inventory, security]
---

# Problem: Monthly inventory was a risky absolute-override surface

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Executives still had Inventory → Monthly inventory: a broad “Set” UI that rewrote absolute stock across honey, materials, raw bags, dept, and RTS after the administrator password. Inventory Check + Products Edit + System Logs made it redundant and easy to misuse.

## Cause

→ [[causes/monthly-inventory-absolute-override]]

## Fix

→ [[fixes/remove-monthly-inventory]]

## Files

| Role | Path / note |
|------|-------------|
| Deleted | `app/src/app/pages/exec/MonthlyInventoryPage.tsx` |
| Nav/route | `Sidebar.tsx`, `routes.ts` (redirect `/inventory/monthly-count` → `/logs`) |
| Perm | `includes/permissions.php`, `permissionKeys.ts`, `permissions.ts` |
| API | Removed `seed_*`, `dept_raw_set_bags`; stripped `monthly_inventory` admin-pw branches in honey/materials/rts |
| Helpers | Removed `honey_executive_seed_production_row` / `honey_executive_seed_packaging_row` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- No SQL migration

## Links

- **Spec:** [[specs/2026-11-13_remove-monthly-inventory]]
- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
