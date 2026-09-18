---
date: 2026-09-18
type: problem-record
status: fixed
area: sales
tags: [problem]
---

# Problem: Monthly Sales export blocked at 5,000 shipped rows

## Symptom

Custom ~1-month Sales range (~16k shipped) failed Excel export with “max 5000”.

## Cause

→ [[causes/sales-export-single-sheet-row-cap]]

## Fix

→ [[fixes/sales-excel-chunked-multi-sheet-export]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` |
| API | `api/v1/sales.php` `honey_sold_export` (unchanged action) |

## Deploy

- PHP only (hard refresh not required for API)

## Links

- **Fragile edges:** [[003_Fragile_Edges#Online Orders / inventory / sales]]
