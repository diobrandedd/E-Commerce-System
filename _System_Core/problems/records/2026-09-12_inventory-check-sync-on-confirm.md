---
date: 2026-09-12
type: problem-record
status: fixed
area: inventory-check
tags: [problem, inventory-check, stock-sync]
---

# Problem: Check inventory confirm did not update stock

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Ops treated Audit vs system audited totals as the true on-hand, but **Confirm and save** only froze variance history and closed the session. Unique barcodes missing from the audit stayed in stock.

## Cause

→ Spec historically locked “scanning/confirm never mutates stock”; Monthly inventory was the only absolute override.

## Fix

→ [[specs/2026-09-12_inventory-check-sync-on-confirm]] · [[plans/archive/2026-09-12_inventory-check-sync-on-confirm_plan]]

Confirm and update inventory (administrator password): freeze variance → void missing scoped uniques when uniques were audited → set honey/dept aggregates to audited → mark run `stock_applied`.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/inventory_check_apply.php` |
| API | `api/v1/inventory_check.php` (`finalize`, `finalize_merged`) |
| Migration | `sql/migration_phase82_inventory_check_stock_apply.sql` |
| SPA | `InventoryCheckPage.tsx`, `inventoryCheck.ts`, history detail |

## Deploy

- Run phase 82 SQL
- PHP + `cd app && npm run build` + hard refresh
- Set administrator password in System Settings if unset

## Links

- **Fragile edges:** [[003_Fragile_Edges]] inventory check bullet
