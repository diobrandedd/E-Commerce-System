# Problem: No shared barcode verify for all roles

## Problem
Staff across roles needed a way to check any sticker (honey or Paragis/BRC/Cacao) without honey-ops or dept-ops permissions — supplier/batch label, product content, batch date, and scan-in time.

## Cause
Lookups lived behind `bottle_barcodes.php` / `dept_pouch_barcodes.php` with ops role gates. There was no unified read-only endpoint or SPA page for all accounts.

## Fix
- `includes/barcode_verify.php` — tries honey then dept pouch tables; returns a common shape.
- `api/v1/barcode_verify.php?action=lookup` — any authenticated IMS user (GET/POST).
- SPA `/verify-barcode` with type/camera scan UI; sidebar **Verify barcode** for executive, PM, HR, TL, staff, CSR TL/staff.

## Files
- `includes/barcode_verify.php`
- `api/v1/barcode_verify.php`
- `app/src/app/lib/barcodeVerify.ts`
- `app/src/app/pages/BarcodeVerifyPage.tsx`
- `app/src/app/routes.ts`
- `app/src/app/components/Sidebar.tsx`
