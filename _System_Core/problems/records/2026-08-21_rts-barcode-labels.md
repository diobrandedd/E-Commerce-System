---
date: 2026-08-21
type: problem-record
status: fixed
area: rts-barcode-labels
tags: [rts, barcodes]
---

# Problem: Unlabeled RTS units needed batch barcode labels

## Symptom

RTS stock often had no stickers. Printing one batch at a time wasted paper when lots were only 2–10 units across many batch numbers. Staff needed multi-batch generate + continuous print, then normal stock-in.

## Cause

→ [[causes/rts-unlabeled-no-batch-print]]

No RTS module minted honey/dept barcodes with manual batch/date, and existing print sheets were one barcode batch per job (no continuous multi-lot packing).

## Fix

→ [[fixes/rts-multi-batch-barcode-labels]]

Added RTS **Barcode labels** (`rts.barcode_labels`): multi-line job create, `source=rts_label` on honey/dept batches, continuous A4 print with per-batch banners that pack onto the same page.

## Files

| Role | Path / note |
|------|-------------|
| Spec | `_System_Core/specs/2026-08-21_rts-barcode-labels.md` |
| Migration | `sql/migration_phase66_rts_barcode_labels.sql` |
| Include | `includes/rts_barcode_labels.php` |
| API | `api/v1/rts_barcode_labels.php` |
| SPA | `app/src/app/pages/rts/RTSBarcodeLabelsPage.tsx` |

## Deploy

- Run phase 66 SQL; grant `rts.barcode_labels`; hard-refresh SPA

## AI rules (optional)

RTS label stock-in increments inventory like `request` (not `backfill`). Print must stay continuous — do not force a page break per batch line.
