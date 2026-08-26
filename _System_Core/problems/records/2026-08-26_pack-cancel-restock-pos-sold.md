---
date: 2026-08-26
type: problem-record
status: fixed
area: deliveries-cancelled-restock
tags: [problem]
---

# Problem: Cancelled restock rejected POS walk-in sold barcodes

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Deliveries → Cancelled only accepted packed (courier) stickers. POS walk-in sold barcodes could not be scanned back; restock always credited packaging/finished even when the unit originated from RTS sellable.

## Cause

→ [[causes/pack-cancel-restock-packed-only]]

## Fix

→ [[fixes/pack-cancel-restock-sold-and-pool]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/pack_cancel_restock.php` |
| Related | [[generated/modules/pack_cancel_restock]] |
| SPA | `app/src/app/pages/deliveries/DeliveriesCancelledPage.tsx` |
| SPA | `app/src/app/components/fulfillment/DeliveriesCancelledGuideModal.tsx` |
| Client | `app/src/app/lib/packCancelRestock.ts` |

## Deploy

- PHP + SPA build (`cd app && npm run build`) + hard refresh; regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges]]
- Spec cutover: [[specs/2026-08-25_rts-barcode-inventory-cutover]]
