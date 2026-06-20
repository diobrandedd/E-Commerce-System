---
date: 2026-06-19
type: problem-record
status: fixed
area: waybill-stamper
tags: [problem, stamp, pdf]
---

# Problem: Waybill stamp box border too heavy

## Symptom

Stamped waybills showed a white rectangle + black border (or red box for NO MATCH) behind item text. CSR wanted **text only** on the label.

## Fix

→ [[fixes/waybill-stamp-text-only-no-border]]

## File

- `waybill-stamper/processor.py` — removed `draw_rect` in `stamp_page()`

## Deploy

Re-stamp PDFs; no migration or SPA build.
