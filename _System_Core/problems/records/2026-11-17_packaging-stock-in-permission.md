---
date: 2026-11-17
type: problem-record
status: fixed
area: packaging-permissions
tags: [problem]
---

# Problem: Packaging stock-in required executive role despite permission grant

## Symptom

Staff granted all packaging permissions still saw **Update price** / **Set on-hand** but no **Stock in lot** on Supplies item detail.

## Cause

→ [[causes/packaging-stock-in-exec-hard-gate]]

## Fix

→ [[fixes/packaging-stock-in-permission-only]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/material_inventory.php` (`material_inventory_role_can_stock_in_packaging`) |
| Route | `app/src/app/routes.ts`, `app/src/app/lib/permissions.ts` |
| SPA | `PackagingMaterialsPage.tsx`, `PackagingMaterialDetailModal.tsx` |

## Deploy

- PHP + SPA rebuild · hard refresh · grant `packaging.stock_in` in Staff Permissions
