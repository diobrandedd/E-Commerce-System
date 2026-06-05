---
type: fix
tags: [fix, waybill, tracking]
---

# Fix: Partner stamp OCR confusables + first sheet only

## What we changed

- `ocr_confusables_for_match()` / `online_orders_ocr_confusables_for_match()` — O→0 for Flash/Lazada P-IDs
- Read only `wb.sheetnames[0]` / `getSheet(0)` for iPYX daily workbook

## Verify

Flash waybill `P3623KKO3MYAD` + Excel `P3623KK03MYAD` → MATCHED; no extra rows from other sheets.

## Related problems

- [[records/2026-06-05_partner-waybill-flash-no-match]]
