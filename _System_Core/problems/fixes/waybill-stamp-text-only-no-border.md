---
date: 2026-06-19
type: fix
area: waybill-stamper
tags: [fix, stamp, pdf]
---

# Fix: Waybill stamp — text only (no box/border)

## Change

`waybill-stamper/processor.py` → `stamp_page()` no longer calls `page.draw_rect()`.

- **Matched:** black text `ITEMS: …` bottom-right
- **No match:** red text `NO MATCH: …` (no red fill/border box)

Layout (`_stamp_layout`, `_draw_stamp_text`) unchanged — SPX bottom-left QR reserve preserved.

## File

- `waybill-stamper/processor.py`

## Deploy

Python only — **no SPA rebuild**. Re-stamp PDFs to see change; old stamped files unchanged.

## Related

- [[003_Fragile_Edges#Waybill item stamp (PDF)]]
- [[modules/waybill_stamper]]
