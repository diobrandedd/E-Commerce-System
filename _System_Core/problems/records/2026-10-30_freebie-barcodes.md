---
date: 2026-10-30
type: problem-record
status: fixed
area: dept-raw-freebies
tags: [freebie, barcode, pack-scan]
---

# Problem: Freebies had no barcode, so stock-in and pack-out were manual

> Paragis, Black Rice, and Jar Cacao freebies now have one permanent barcode each: scan to stock in on the raw page, scan in Pack scan to give out, print all three from Settings.

## Symptom

Freebies were created with a + Freebies button and given out by typing a quantity. They could not be scanned, and there was no sticker to print.

## Cause

→ [[causes/freebie-no-barcode]]

## Fix

→ [[fixes/freebie-barcodes]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase96_dept_raw_freebie_barcodes.sql` |
| Includes | `includes/dept_raw_freebies.php`, `includes/sku_barcodes.php`, `includes/honey_bottle_barcodes.php`, `includes/inventory_check.php`, `includes/barcode_verify.php`, `includes/pack_cancel_restock.php` |
| API | `api/v1/sku_barcodes.php` (`freebie_list`, `freebie_save_bulk`, `freebie_print_sheet`), `api/v1/dept_pouch_barcodes.php` `scan_stock_in` |
| SPA | `SettingsPage.tsx`, `DeptPouchBarcodePanels.tsx`, `RawDeptMaterialPage.tsx`, `HoneyPackScanPage.tsx` |
| Spec | [[specs/2026-10-30_freebie-barcodes]] |

## Deploy

- Run phase 96 on the database, then `cd app && npm run build` and hard-refresh Apache `app/dist/`.

## AI rules (optional)

Freebie codes live in `dept_raw_freebie_barcodes`, never in `product_sku_barcodes`. POS, RTS stock-in, and leak/damage treat every non-honey SKU as finished stock.
