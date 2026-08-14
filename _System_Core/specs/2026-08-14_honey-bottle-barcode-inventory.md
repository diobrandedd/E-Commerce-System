---
status: approved
created: 2026-08-14
area: honey
tags: [honey, barcodes, inventory, pos, parcels, sales]
---

# Honey Bottle Barcode Inventory

## Summary

Track every honey bottle as a unique Code128 unit. Staff request labels (variant + size + qty). Admin generates a batch tied to supplier code + bottling date; the sticker is a short unique 6-character lookup (letters + digits, e.g. `K7M2P9`). Scanning a sticker is stock-in to packaging. Pack sessions, POS, and RTS consume those units. Aggregate `honey_packaging_rows.current_*` stays a mirror of unit counts.

Honey is the only inventory counted this way. Waybill QR + photo is shipping confirmation only. Dept product and fulfillment packaging deduction on ship are switched off until honey runs clean.

## Roles

| Action | staff / TL | prod_manager | executive |
|--------|------------|--------------|-----------|
| Create request | yes | yes | yes |
| Generate / print / void / backfill | no | yes | yes |
| Scan stock-in | yes | yes | yes |
| Pack session | yes | yes | yes |
| POS scan checkout | POS pin | POS pin | POS pin |
| RTS bottle scan | RTS roles | RTS roles | yes |
| Sales report / reconcile | no | yes | yes |

## Out of scope

- Dept product (Paragis, Cacao, BRC, Honey Comb) barcode tracking
- Fulfillment packaging consumption at pack/ship
- Online parcel peso revenue (no price column on parcels)
- Camera Code128 scanning (keyboard-wedge first)

## Links

- Plan: [[plans/active/2026-08-14_honey-bottle-barcode-inventory_plan]]
