---
date: 2026-09-15
type: problem-record
status: fixed
area: sales
tags: [sales, export, excel]
---

# Problem: Sales page had no Excel export for walk-in + shipped

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Executives needed an Excel download of Sales filtered by period / All / Walk-in / Shipped, with pack-scan (shipped) rows separate from walk-in POS checkouts, including whether a line was RTS-resellable scanned.

## Cause

→ [[causes/sales-no-excel-export]]

## Fix

→ [[fixes/sales-excel-two-sheet-export]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` |
| API | `api/v1/sales.php` (`honey_sold_export`) |
| SPA client | `app/src/app/lib/api.ts` (`apiDownload`) |
| SPA | `app/src/app/pages/exec/SalesPage.tsx` |
| Related | [[generated/modules/honey_sales_report]] |

## Deploy

- `cd app && npm run build` + hard refresh Apache `app/dist/`
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Sales report]]
- **Plan:** [[plans/archive/2026-09-15_sales-excel-export_plan]]
- **Related:** [[specs/2026-09-10_sales-view-amounts]]
