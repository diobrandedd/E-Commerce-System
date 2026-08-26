---
date: 2026-08-26
type: problem-record
status: fixed
area: sales-pos-return
tags: [problem]
---

# Problem: Returned POS barcode still counted in receipt total

## Symptom

After Cancelled restock of a sold walk-in bottle, Sales still showed the full receipt total (e.g. ₱3,500 for 7×₱500) even though one barcode was marked returned.

## Cause

→ [[causes/pos-return-no-receipt-money]]

## Fix

→ [[fixes/pos-return-receipt-recalc]]

## Files

| Role | Path |
|------|------|
| Primary | `includes/honey_pos.php` (`pos_sale_reverse_one_unit`, heal) |
| Restock | `includes/pack_cancel_restock.php` |
| Sales API | `includes/honey_sales_report.php` |
| SPA | `app/src/app/pages/exec/SalesPage.tsx` |

## Deploy

- PHP + SPA build + hard refresh. Opening Sales heals already-returned honey lines on that page.
