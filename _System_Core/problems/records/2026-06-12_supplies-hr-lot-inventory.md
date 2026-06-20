---
date: 2026-06-12
type: problem-record
status: fixed
area: supplies-inventory
tags: [supplies, hr, lot, wac]
---

# Problem: Supplies lacked lot stock-in and HR issue workflow

## Symptom

Supplies used flat add/deduct with `current_price` stuck at 0; prod managers requested adjustments; no requester tracking for staff issues; per-department catalog rows.

## Cause

Legacy `MaterialsCategoryPage` delta adjust model — packaging had already moved to lot WAC + Requests, but supplies did not.

## Fix

→ [[fixes/supplies-hr-lot-inventory]]

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase25_supplies_hr_lot.sql` |
| Backend | `includes/material_inventory.php`, `api/v1/materials.php` |
| SPA | `SuppliesMaterialsPage.tsx`, `Supply*Modal.tsx`, `InventoryRequestsPage.tsx` |
| Client | `app/src/app/lib/materials.ts` |

## Deploy

1. `migration_phase25_supplies_hr_lot.sql`
2. `cd app && npm run build`
3. `php tools/memory_bank/generate_all.php`

## Links

- **Spec:** [[specs/2026-06-12_supplies-hr-lot-inventory]]
- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement]]
- **Patterns:** [[005_SPA_UX_Patterns#Supplies inventory pages]]
