---
date: 2026-06-05
type: problem-record
status: fixed
area: shopee-spx
tags: [problem, shopee-spx, tracking]
---

# Problem: Shopee SPX master list showed 0 instead of O in tracking

## Symptom

Packing list Excel + PDF import — stamping OK, but **master list download** showed `PH2623915201000` instead of `PH26239152010O` on row 2. Wrong TN flowed to online parcels.

## Cause

→ [[causes/ocr-confusables-ph-tracking]]

## Fix

→ [[fixes/narrow-ocr-confusables-shopee-prefix]]

## Files

| Role | Path / note |
|------|-------------|
| PHP | `includes/online_orders_generator.php` |
| Python | `waybill-stamper/processor.py` |
| Module map | [[generated/modules/online_orders_generator]] |

## Deploy

- PHP + Python only — **no SPA rebuild**
- Re-import batch or manually fix existing `PH2623915201000` rows in DB/parcels

## Links

- **Fragile edges:** [[003_Fragile_Edges#Partner waybill matching (iPYX Excel + Flash OCR)]]

## AI rules

Confusables are for Flash/Lazada marketplace P-IDs only — not Shopee express `PH…` trackings.
