---
date: 2026-09-04
type: problem-record
status: fixed
area: pack-scan
tags: [problem, honey, sku, barcodes]
---

# Problem: Pack scan fails when mixing SKU and unique stickers

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Pack confirm worked with unique stickers only or SKU qty only, but mixed sessions showed **Not enough packaging stock to deduct this bottle** even when Products inventory had enough Dark/Amber stock.

## Cause

→ [[causes/pack-confirm-sku-before-unique]]

## Fix

→ [[fixes/pack-confirm-unique-before-sku]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` (`honey_bottle_pack_session_confirm`) |
| UI note | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |
| Related | [[generated/modules/honey_bottle_barcodes]] · [[generated/modules/sku_barcodes]] |

## Related

- [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]
- [[specs/2026-08-26_sku-barcode-inventory]]
