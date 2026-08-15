# Problem: Paragis Tea finished pouches had no unit barcodes; Pouch deducted WIP/packaging

## Problem
Dried Paragis finished stock was only increased via the Pouch button, which deducted WIP tea bags and packaging formula. Ops needed honey-style stickers with a custom batch code (e.g. `JR073026`) and scan-to-stock-in without WIP/packaging deducts.

## Cause
Dept finished qty lived only as an aggregate on `dept_inventory_rows`; there was no request → generate → print → scan pipeline for Paragis pouches.

## Fix
- Migration phase57: `paragis_pouch_*` tables (requests, batches, barcodes, codes_issued, units, seq)
- `includes/paragis_pouch_barcodes.php` + `api/v1/paragis_pouch_barcodes.php`
- Scan stock-in increments finished pouches only (no WIP/packaging deduct)
- `dept_pt_pouch` API returns 410; SPA removes Pouch and adds Request / Scan / Generate-print on Dried Paragis

## Files
- `sql/migration_phase57_paragis_pouch_barcodes.sql`
- `includes/paragis_pouch_barcodes.php`
- `api/v1/paragis_pouch_barcodes.php`
- `api/v1/honey.php` (gate `dept_pt_pouch`)
- `app/src/app/lib/paragisPouchBarcodes.ts`
- `app/src/app/components/paragis/ParagisPouchBarcodePanels.tsx`
- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `_System_Core/specs/2026-08-15_paragis-pouch-barcode-inventory.md`
