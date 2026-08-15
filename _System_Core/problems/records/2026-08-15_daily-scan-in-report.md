---
date: 2026-08-15
area: inventory-reporting
spec: specs/2026-08-15_daily-scan-in-report
---

# Inventory pages lacked a gross daily scan-in report

## Problem

Honey Packaging displayed `honey_packaging_rows.current_*`, so packing and POS
deductions reduced the visible batch quantities. Dept barcode panels showed
request-level scan progress but had no calendar-date report. Staff could not
answer how many finished units were scanned into the system on a given day.

## Cause

Both barcode systems already retained the canonical stock-in event
(`scanned_at`, `scanned_by`), but APIs only exposed current aggregates or
request/batch drilldowns. The drilldowns also required barcode status
`scanned`, which could omit a historically scanned sticker after a later void.

## Fix

- Honey `packaging_scan_report`: groups all barcode rows with `scanned_at` by
  packaging row and size, with Today / historical date / All dates filtering.
- Dept `scan_report`: shared gross scan counts for Paragis Tea, BRC, Jar Cacao,
  and Honey Comb, grouped by request/batch.
- Both drilldowns accept the same date and count `scanned_at IS NOT NULL`
  regardless of later unit status.
- Honey Packaging now displays gross scanned totals; current stock remains on
  Products → Honey. Shared dept panels use the same today-default selector.
- Selected-day summaries: honey `totals.by_variant` + grand total; dept
  `scanned_total`. Counters are hidden when All dates is selected.
- No schema migration.

## Files

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `api/v1/bottle_barcodes.php`
- `api/v1/dept_pouch_barcodes.php`
- `app/src/app/lib/bottleBarcodes.ts`
- `app/src/app/lib/deptPouchBarcodes.ts`
- `app/src/app/pages/honey/HoneyPackagingPage.tsx`
- `app/src/app/components/honey/PackagingScanLogModal.tsx`
- `app/src/app/components/dept/DeptPouchBarcodePanels.tsx`
- `app/src/app/components/dept/DeptPouchScanLogModal.tsx`
