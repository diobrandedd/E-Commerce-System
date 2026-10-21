---
date: 2026-10-21
type: problem-record
status: fixed
area: packaging-inventory
tags: [supplies, roles, scan]
---

# Problem: Supplies catalog visible beyond executives and assigned stock-out staff

> Assigned staff could open the inventory table and report damage. HR could open Supplies from view permission alone.

## Symptom

Supplies showed the full catalog to anyone with `packaging.view`. Assigned staff should stock out and report damage without that table.

## Cause

→ [[causes/packaging-no-damage-pool]]

## Fix

→ [[fixes/supplies-scan-only-assigned]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `PackagingMaterialsPage.tsx`, `routes.ts`, `permissions.ts` |
| API | `api/v1/materials.php` `packaging_report_damage` stays on `packaging.deduct` |
| Roles | `includes/permissions.php` |

## Deploy

- `cd app && npm run build` + hard-refresh Apache `app/dist/`
