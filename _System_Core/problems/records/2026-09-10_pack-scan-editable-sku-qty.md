---
date: 2026-09-10
type: problem-record
status: fixed
area: deliveries-pack-scan
tags: [problem, pack-scan, sku, session, inventory]
---

# Problem: Pack scan session SKU qty was not editable

## Symptom

On Deliveries → Pack scan, non-unique (SKU) session lines showed a static quantity. Operators could only add more via scan/bulk Add or delete the line — they could not correct the amount before Confirm.

## Cause

→ [[causes/pack-scan-sku-qty-not-editable]]

## Fix

→ [[fixes/pack-scan-editable-sku-qty]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` (`pack_set_item_qty`) |
| SPA client | `app/src/app/lib/bottleBarcodes.ts` |
| SPA UI | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |

## Deploy

- `cd app && npm run build` + hard refresh
- No migration

## Links

- **Module:** [[generated/modules/honey_bottle_barcodes]]
- **AI rule:** Unique pack stickers stay one-per-row; only SKU lines may use `pack_set_item_qty`.
