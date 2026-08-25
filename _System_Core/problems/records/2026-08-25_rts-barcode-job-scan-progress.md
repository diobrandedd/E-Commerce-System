---
date: 2026-08-25
type: problem-record
status: fixed
area: rts
tags: [rts, barcodes, labels, ux]
---

# Problem: RTS label jobs hid scan progress and codes

> Recent jobs listed print metadata only — no scanned/generated ratio or barcode list.

## Symptom

Staff could reprint RTS label jobs but could not see how many stickers had been stocked in or which codes belonged to a job.

## Cause

Job list API returned line/label counts only; barcode rows lived on honey/dept batch tables with no job-level aggregate or expand payload.

## Fix

- `job_list` / `job_get` include `code_count` + `scanned_count` (`status = scanned`)
- New `job_codes` action returns each code with status / scan time
- Recent jobs UI: expandable rows, ratio + progress bar, lazy-loaded code table

Schema unchanged. Note: “scanned” today means inventory stock-in (packaging/finished); RTS-pool cutover is a separate draft spec.

## Files

- `includes/rts_barcode_labels.php`
- `api/v1/rts_barcode_labels.php`
- `app/src/app/lib/rtsBarcodeLabels.ts`
- `app/src/app/pages/rts/RTSBarcodeLabelsPage.tsx`

## Related

- [[specs/2026-08-21_rts-barcode-labels]]
- [[specs/2026-08-25_rts-barcode-inventory-cutover]] (draft)
