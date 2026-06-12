---
date: 2026-06-12
area: packaging-inventory
tags: [packaging, roles, hr, prod_manager, executive, guide]
---

# Packaging inventory role split + guide UX

## Problem

Packaging Materials UI and guides did not match operational roles: HR receives stock, production manager records usage, executive needs full control and logs. Guides used inline panels and linked non-executive users to Product Costing.

## Cause

Early packaging UX treated executive and HR symmetrically (both stock-in, HR count requests) and left production manager read-only. No manual **deduct for use** path on the packaging page.

## Fix

1. **Roles** — HR `stock_in_lot`; prod manager `packaging_deduct`; executive all actions + `set_on_hand` + pool yield + 100-row log.
2. **API** — `material_packaging_deduct_for_use()`, `packaging_detail.permissions`, role helpers in `material_inventory.php`.
3. **SPA** — `PackagingDeductModal`, updated detail modal and `PackagingInventoryGuideModal` (header Guide button, no costing links for HR/PM).
4. **Costing** — executive-only route and API (separate entry in memory bank).
5. **Docs / rules** — [[fixes/packaging-inventory-roles]], [[fixes/inventory-role-permissions]], [[fixes/page-guide-button-modal]], `.cursor/rules/inventory-roles-and-guides.mdc`.

## Files

- `includes/material_inventory.php`
- `api/v1/materials.php`
- `app/src/app/pages/inventory/PackagingMaterialsPage.tsx`
- `app/src/app/components/inventory/PackagingMaterialDetailModal.tsx`
- `app/src/app/components/inventory/PackagingDeductModal.tsx`
- `app/src/app/components/inventory/PackagingInventoryGuideModal.tsx`
- `app/src/app/lib/materials.ts`

## Reusable patterns

- [[fixes/packaging-inventory-roles]]
- [[fixes/inventory-role-permissions]]
- [[fixes/page-guide-button-modal]]
