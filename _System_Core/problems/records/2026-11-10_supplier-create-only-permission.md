---
date: 2026-11-10
type: problem-record
status: fixed
area: suppliers
tags: [problem, permissions]
---

# Problem: Could not grant Supplier Master create without edit/deactivate

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Some users needed to open Supplier Master and add suppliers, but not edit rows or deactivate them. The only write permission was `suppliers.manage`, which covered create, edit, activate/deactivate, and purchase prices together.

## Cause

→ [[causes/supplier-manage-all-or-nothing]]

## Fix

→ [[fixes/supplier-create-permission]]

## Files

| Role | Path / note |
|------|-------------|
| Catalog | `includes/permissions.php` (`suppliers.create`) |
| API | `api/v1/suppliers.php` (`create` accepts manage OR create) |
| SPA keys | `app/src/app/lib/permissionKeys.ts` |
| Helpers | `app/src/app/lib/honeyPermissions.ts` (`canCreateSuppliers`) |
| Page | `app/src/app/pages/exec/SuppliersPage.tsx` |
| Guide | `app/src/app/components/suppliers/SuppliersGuideModal.tsx` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Grant `suppliers.view` + `suppliers.create` in Staff Management (leave Manage unticked)

## Links

- **Fragile edges:** [[003_Fragile_Edges]]
