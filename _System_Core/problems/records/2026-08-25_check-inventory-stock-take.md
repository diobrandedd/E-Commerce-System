---
date: 2026-08-25
area: inventory
tags: [inventory, stock-take, barcodes]
---

# Check Inventory (physical stock take)

## Problem

Nobody could verify system inventory against the physical shelf. When a manager decided today was a counting day, staff had no tool to record what they saw, and no variance report could be produced afterwards.

Cause: [[causes/no-physical-count-tool]]
Fix: [[fixes/inventory-check-daily-count]]

## Symptom

System stock drift went unnoticed until monthly inventory, with no record of who counted what.

## Cause

Barcodes existed for every finished unit, but the only scan flows mutated stock (pack, ship, leak/damage). There was no read-only "I can see this unit" record and no comparison report.

## Fix

New `inventory_check` module:

- Shared daily count event (`inventory_check_events`), opened/closed by `inventory.check.manage`
- Every user with `inventory.check.scan` can scan **any** finished product; scans never touch stock
- `inventory_check_scans` snapshots supplier, batch, bottling date, variant, size and flags units the system does not show in stock
- `inventory.check.view_all` unlocks summary, per-row scan log (who scanned each code), compare, and dated history
- Compare is scoped to counted product lines and persisted as `inventory_check_compare_runs` + `inventory_check_variance_lines` so a report stays reproducible

## Files

- `sql/migration_phase67_inventory_check.sql`
- `includes/inventory_check.php`
- `api/v1/inventory_check.php`
- `includes/permissions.php`
- `app/src/app/lib/inventoryCheck.ts`
- `app/src/app/pages/inventory/InventoryCheckPage.tsx`
- `app/src/app/components/inventory/InventoryCheckScanLogModal.tsx`
- `app/src/app/routes.ts`, `app/src/app/lib/permissions.ts`, `app/src/app/components/Sidebar.tsx`

## Notes

Honey variance is per packaging batch and size; dept system stock is product-level only, so dept variance is per product with counted batches attached as detail. Every honey batch holding stock is included in a compare, so an uncounted batch reads as short — that is intentional.
