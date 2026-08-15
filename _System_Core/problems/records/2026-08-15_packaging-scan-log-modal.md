# Problem: Packaging batch rows did not show who scanned stickers in

## Problem
On Raw Honey → Packaging, tapping a batch only opened the old JSON traceability drawer. Ops needed to see **who** scanned each sticker into inventory and **when**.

## Cause
`scanned_by` / `scanned_at` live on `honey_bottle_barcodes` but no list API or UI exposed them by packaging row. Traceability matched short custom batch codes only via `supplier_code` / `batch_no LIKE 'code-%'`, missing exact `batch_no = JR063026`.

## Fix
- `honey_bottle_packaging_scan_log()` + API `packaging_scan_log` (by `packaging_row_id`, paginated).
- Packaging table: whole row opens `PackagingScanLogModal` (code, size, scanner name, time, unit status).
- Trace search also matches exact `batch_no` for short labels like `JR063026`.
- Table uses horizontal scroll + `min-w` so mobile no longer clips size columns awkwardly.

## Files
- `includes/honey_bottle_barcodes.php`
- `includes/honey_inventory.php` (trace batch_no exact match)
- `api/v1/bottle_barcodes.php`
- `app/src/app/lib/bottleBarcodes.ts`
- `app/src/app/components/honey/PackagingScanLogModal.tsx`
- `app/src/app/pages/honey/HoneyPackagingPage.tsx`
