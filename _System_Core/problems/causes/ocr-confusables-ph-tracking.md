---
type: cause
tags: [cause, shopee-spx, tracking]
---

# Cause: OCR confusables O→0 applied to Shopee PH trackings

## What went wrong

`online_orders_ocr_confusables_for_match()` and Python `ocr_confusables_for_match()` mapped **all** `O` → `0` on `^P[A-Z0-9]+$`. Shopee express IDs like `PH26239152010O` match that pattern; legitimate trailing **O** became **0** in DB/master (`PH2623915201000`) while stamp Excel kept raw SPA tracking.

## Where in code

- `online_orders_ocr_confusables_for_match()` in `includes/online_orders_generator.php`
- `ocr_confusables_for_match()` in `waybill-stamper/processor.py`
- Called via `online_orders_normalize_tracking()` → master merge + duplicate checks

## Related problems

- [[records/2026-06-05_shopee-spx-tracking-o0]]

## Prevent

Never apply O→0 to `PH` / `SPEPH` / `SPX` — see [[003_Fragile_Edges#Partner waybill matching (iPYX Excel + Flash OCR)]].
