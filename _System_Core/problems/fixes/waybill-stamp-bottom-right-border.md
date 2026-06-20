---
date: 2026-06-19
type: fix
area: waybill-stamper
tags: [fix, stamp, pdf]
---

# Fix: Waybill stamp — bottom-right with border (all carriers)

## Change

Reverted mistaken SPX center placement. `stamp_page()`:

- Bordered box (white/black match, red no-match)
- **Bottom-right** on every label
- SPX/Shopee: `left_reserve = 42%` so stamp does not cover bottom-left QR

## File

- `waybill-stamper/processor.py`

## Deploy

Python only — re-stamp PDFs.
