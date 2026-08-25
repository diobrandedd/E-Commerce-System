---
date: 2026-08-25
type: problem-record
status: fixed
area: rts
tags: [rts, barcodes, inventory, cutover, pos, packing]
---

# Problem: RTS sellable inventory was case-sync, not barcode stock

> Sellable RTS on-hand mirrored case lines while RTS barcode labels stocked into packaging — misleading and not barcode-truth.

## Symptom

Generating/scanning RTS labels did not feed RTS inventory. Case save + sync invented sellable qty. Pack/POS could use qty Use RTS without unit barcodes.

## Cause

→ Case sync always wrote sellable `rts_inventory_rows`; honey/dept stock-in always used packaging/finished pools; pack confirm always deducted packaging.

## Fix

- Phase 68: `sellable_from_cases=0`, hard-reset sellable currents to 0, `dept_pouch_units.pool`
- Sync skips sellable inserts; damaged sync kept
- `stock_in_scan` → `rts_sellable` + `in_stock` + credit RTS rows
- Pack/POS consume RTS pool units; qty Use RTS blocked when cutover on
- SPA: RTS inventory scan-in, Record/labels copy, Sales RTS counts
- Cases/records never wiped

## Files

- `sql/migration_phase68_rts_barcode_inventory_cutover.sql`
- `includes/rts_inventory_sync.php`, `honey_bottle_barcodes.php`, `dept_pouch_barcodes.php`, `rts_case_handlers.php`, `honey_pos.php`, `dept_pos.php`, `honey_sales_report.php`
- `api/v1/rts.php`
- `app/src/app/pages/RTSPage.tsx`, `rts/RTSRecordPage.tsx`, `rts/RTSBarcodeLabelsPage.tsx`, POS + Sales
- Spec/plan: [[specs/2026-08-25_rts-barcode-inventory-cutover]]

## Deploy

```text
mysql … < sql/migration_phase68_rts_barcode_inventory_cutover.sql
```

First run enables cutover + zeros sellable. Dump sellable currents before prod if rollback of numbers may be needed.
