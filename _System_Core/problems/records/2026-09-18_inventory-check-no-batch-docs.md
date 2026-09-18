---
date: 2026-09-18
type: problem-record
status: fixed
area: inventory-check
tags: [inventory-check, pools, barcodes]
---

# Problem: Check Inventory still required batch + bottling date

> After honey packaging pools, auditors should scan then enter quantity only — barcodes already carry variant and size.

## Symptom

Add-to-list forced batch number and bottling date on every scan, even though finished stock matches by product / variant / size (POOL), not by lot docs.

## Cause

→ [[causes/inventory-check-batch-docs-required]]

## Fix

→ [[fixes/inventory-check-scan-qty-only]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/inventory_check.php` |
| Migration | `sql/migration_phase85_inventory_check_code_unique.sql` |
| API | `api/v1/inventory_check.php` |
| SPA | `app/src/app/pages/inventory/InventoryCheckPage.tsx` |
| Spec | [[specs/2026-09-18_inventory-check-no-batch-docs]] |
| Related | [[generated/modules/inventory_check]] |

## Deploy

- Run phase85 SQL if not applied; PHP includes; `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges]]
- **Prior:** [[specs/2026-09-11_inventory-check-batch-docs]] · [[specs/2026-09-17_honey-packaging-pool-no-lots]]
