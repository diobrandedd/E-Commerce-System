---
date: 2026-06-19
type: fix
area: waybill-stamper
tags: [fix, stamp, pdf, spx]
---

# Fix: SPX stamp — bordered box in COD / buyer-seller zone

## Change

`waybill-stamper/processor.py`:

- Restored `page.draw_rect()` — white fill + black border (match), red fill + border (no match).
- **SPX/Shopee labels:** stamp centered horizontally at ~42% page height (buyer/seller band, same area as COD watermark).
- **Other carriers:** bottom-right placement unchanged.

## File

- `waybill-stamper/processor.py`

## Deploy

Python only — re-stamp PDFs to apply.
