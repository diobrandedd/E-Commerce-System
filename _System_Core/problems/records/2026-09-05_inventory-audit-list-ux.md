---
date: 2026-09-05
type: problem-record
status: fixed
area: inventory-check
tags: [problem, inventory, audit]
---

# Problem: Check inventory lacked Add-to-list audit UX

## Symptom

Operators needed a clipboard-style inventory audit: add lines via scan modal with qty + batch, edit the session list, then Complete to see audited vs system and either go back or save to history. The old UI was scan-first without required batch or a finalize compare gate.

## Cause

→ [[causes/inventory-check-scan-first-ux]]

## Fix

→ [[fixes/inventory-audit-add-to-list-compare-modal]]

## Files

| Role | Path |
|------|------|
| Primary | `includes/inventory_check.php`, `api/v1/inventory_check.php` |
| SPA | `app/src/app/pages/inventory/InventoryCheckPage.tsx`, `app/src/app/lib/inventoryCheck.ts` |
| Spec | `_System_Core/specs/2026-08-25_check-inventory-stock-take.md` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Regenerate memory bank
