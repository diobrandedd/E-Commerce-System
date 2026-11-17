---
date: 2026-11-17
type: problem-record
status: fixed
area: pos-sales-freebies
tags: [problem]
---

# Problem: POS could not attach freebies; Sales had no freebies-given view

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Staff could not scan or pick Paragis / Black Rice / Jar Cacao freebies on walk-in POS. Recent walk-in sales and Sales reports did not show freebies given (walk-in or shipped).

## Cause

→ [[causes/pos-no-freebie-cart]]

## Fix

→ [[fixes/pos-receipt-freebies]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase99_pos_receipt_freebies.sql` |
| Primary | `includes/pos_unified.php` |
| Refund | `includes/pos_refund.php` |
| Freebie ledger | `includes/dept_raw_freebies.php` (`dept_raw_record_freebie_restock`) |
| Sales | `includes/honey_sales_report.php` |
| API | `api/v1/pos.php` (`unified_freebie_options`) |
| SPA | `app/src/app/pages/pos/POSUnifiedPage.tsx`, `SalesPage.tsx`, `posUnified.ts` |

## Deploy

- Run phase 99 SQL · `cd app && npm run build` + hard refresh · regenerate memory bank

## Links

- **Spec:** [[specs/2026-11-17_pos-freebies]]
- **Plan:** [[plans/archive/2026-11-17_pos-freebies_plan]]
- **Fragile edges:** [[003_Fragile_Edges#Barcode uniqueness across pools]]
