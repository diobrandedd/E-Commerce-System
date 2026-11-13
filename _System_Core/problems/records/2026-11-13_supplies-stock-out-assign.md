---
date: 2026-11-13
type: problem-record
status: fixed
area: supplies-inventory
tags: [supplies, packaging, permissions]
---

# Problem: Assigning stock-out scanners required Staff Permissions

## Symptom

Executives had to open Staff → Permissions to grant `packaging.deduct` even though Supplies already showed who was assigned.

## Cause

→ [[causes/supplies-stock-out-roster-readonly]]

## Fix

→ [[fixes/supplies-stock-out-assign-on-page]]

## Files

| Role | Path |
|------|------|
| Grant/revoke | `includes/permissions.php` |
| Domain | `includes/material_inventory.php` |
| API | `api/v1/materials.php` |
| SPA | `PackagingMaterialsPage.tsx`, `materials.ts` |

## Deploy

- SPA rebuild + hard refresh

## Links

- Spec: [[specs/2026-11-13_supplies-stock-out-assign]]
