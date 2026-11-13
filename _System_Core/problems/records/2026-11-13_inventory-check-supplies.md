---
date: 2026-11-13
type: problem-record
status: fixed
area: inventory-check
tags: [inventory-check, supplies, packaging]
---

# Problem: Supplies could not be counted in Check Inventory

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Supplies (`PKG…` barcodes on `/inventory/supplies`) had stock levels but Check Inventory only resolved honey / dept / freebie. Corrections required manual set-on-hand on the Supplies page.

## Cause

→ [[causes/inventory-check-supplies-out-of-scope]]

## Fix

→ [[fixes/inventory-check-supplies]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase98_inventory_check_supply.sql` |
| Scan / compare / lookup | `includes/inventory_check.php` |
| Apply | `includes/inventory_check_apply.php` |
| Set on-hand source | `includes/material_inventory.php` |
| API | `api/v1/inventory_check.php` (`lookup`) |
| SPA | `InventoryCheckPage.tsx`, `InventoryCheckKindBadge.tsx`, history/applied modals |

## Deploy

- Run phase98 · `cd app && npm run build` · hard refresh · regenerate memory bank

## Links

- Spec: [[specs/2026-11-13_inventory-check-supplies]]
- Plan: [[plans/archive/2026-11-13_inventory-check-supplies_plan]]
- Fragile edges: [[003_Fragile_Edges#Inventory check]]
