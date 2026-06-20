---
type: cause
tags: [cause, waybill, tracking]
---

# Cause: Partner stamp OCR O↔0 mismatch and all-sheet Excel parse

## What went wrong

1. Flash/Lazada PDF OCR read `0` as `O` on marketplace P-IDs → NO MATCH vs Excel.
2. iPYX parser scanned all workbook sheets; only sheet 0 is today's orders — noise/overwrite.

## Where in code

- `waybill-stamper/processor.py` — OCR normalize + `parse_excel()`
- `online_orders_parse_ipyx_excel_paths()` in `includes/online_orders_generator.php`

## Related problems

- [[records/2026-06-05_partner-waybill-flash-no-match]]
- [[records/2026-06-05_shopee-spx-tracking-o0]] (later scope fix for Shopee PH)

## Prevent

First sheet only for iPYX; scoped O→0 confusables — [[003_Fragile_Edges#Partner waybill matching (iPYX Excel + Flash OCR)]]
