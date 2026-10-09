---
status: shipped
spec: "[[specs/2026-10-09_inventory-check-freebies]]"
created: 2026-10-09
approved_by: chat
---

# Implementation Plan: Inventory check freebies

> Spec: [[specs/2026-10-09_inventory-check-freebies]]

## Summary

Drop Freebies Give out UI; add `kind=freebie` inventory check lines via Add-to-list picker; compare/apply `dept_raw_freebie_balances`.

## Files

| File | Action |
|------|--------|
| `sql/migration_phase93_inventory_check_freebie.sql` | create |
| `includes/inventory_check.php` | add freebie scan + compare |
| `includes/inventory_check_apply.php` | apply freebie qty |
| `api/v1/inventory_check.php` | `scan_freebie` |
| `InventoryCheckPage.tsx`, `inventoryCheck.ts` | Freebie mode |
| `RawDeptMaterialPage.tsx` | drop Give out |

## Deploy

- Run phase93 · rebuild SPA · hard refresh
