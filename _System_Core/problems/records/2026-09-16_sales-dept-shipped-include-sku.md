---
date: 2026-09-16
type: problem-record
status: fixed
area: sales-report
tags: [problem]
---

# Problem: Sales under-counted Paragis (and other dept) shipped vs Pack History

## Symptom

Pack scan History showed large Paragis (and other dept) qty for a day (mostly SKU packs), while Sales product cards showed only unique sticker packs (e.g. 09/14: History 197 vs Sales shipped 3; 09/16: 168 vs 48).

## Cause

→ [[causes/sales-dept-shipped-stickers-only]]

## Fix

→ [[fixes/sales-dept-shipped-include-sku]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` (`honey_sales_dept_breakdown`, `honey_sales_fetch_shipped_export`) |
| SPA copy | `app/src/app/pages/exec/SalesPage.tsx` |
| Related | [[generated/modules/honey_sales_report]] |

## Deploy

- PHP + `cd app && npm run build` (or verify) + hard refresh

## Links

- Prior shipped/dept work: [[records/2026-08-15_sales-shipped-and-dept-products]]
- Honey SKU already logged: `sku_barcode_honey_deduct()` → `honey_sales_log`
