# Problem: Warehouse barcode multi-print

**Date:** 2026-09-12

## Problem

Supplier barcode requests could only print one request at a time, wasting paper when several codes needed labels.

## Cause

Print sheet API accepted a single `request_id` only; UI had per-row Print/Reprint only.

## Fix

- Multi-select + **Print selected** (header select-all).
- Bulk generate marks selected printed and opens one combined A4 sheet.
- Full-width banner row separates each supplier/date code on the sheet.

## Files

- `includes/honey_warehouse_barcodes.php`
- `api/v1/honey.php`
- `app/src/app/lib/honeyWarehouseBarcodes.ts`
- `app/src/app/pages/honey/HoneyWarehousePage.tsx`
