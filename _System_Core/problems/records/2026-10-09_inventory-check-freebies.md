---
date: 2026-10-09
type: problem-record
status: fixed
area: inventory-check
tags: [problem, freebies, audit]
---

# Problem: Freebies not in inventory audit; unused Give out

## Symptom

Freebie sample pools could not be counted in Inventory Check (no barcodes). Freebies modal also had a Give out action that was unnecessary once audit can correct balances.

## Cause

→ [[causes/inventory-check-no-freebie-kind]]

## Fix

→ [[fixes/inventory-check-freebie-kind]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase93_inventory_check_freebie.sql` |
| Includes | `includes/inventory_check.php`, `inventory_check_apply.php` |
| API | `api/v1/inventory_check.php` `scan_freebie` |
| SPA | `InventoryCheckPage.tsx`, `inventoryCheck.ts`, `RawDeptMaterialPage.tsx` |
| Spec | [[specs/2026-10-09_inventory-check-freebies]] |

## Deploy

- Run phase93 · `cd app && npm run build` · hard refresh
