---
date: 2026-06-05
type: problem-record
status: fixed
area: partner-stamp
tags: [problem, waybill, tracking]
---

# Problem: Partner stamp NO MATCH on Flash waybill

## Symptom

Flash/Lazada waybill showed NO MATCH; error displayed `P3623KKO3MYAD` vs Excel `P3623KK03MYAD`. Extra wrong rows from other Excel sheets.

## Cause

→ [[causes/partner-stamp-ocr-o-and-all-sheets]]

## Fix

→ [[fixes/partner-ocr-confusables-first-sheet-only]]

## Files

| Role | Path |
|------|------|
| Python | `waybill-stamper/processor.py` |
| PHP | `includes/online_orders_generator.php` |

## Deploy

- PHP + Python only

## Links

- **Fragile edges:** [[003_Fragile_Edges#Partner waybill matching (iPYX Excel + Flash OCR)]]
