---
date: 2026-08-15
status: approved
area: pos-cancel-rts
---

# Spec: POS barcode dept + cancel restock + RTS barcode intake

## Goal

1. POS sells Paragis / BRC / Cacao / Comb only by scanning stickers (cart remove allowed).
2. Deliveries Cancelled restocks packed units by barcode (no tracking QR); history of returns.
3. RTS Record keeps tracking first; optional product barcodes into sellable vs damaged pools.

## Acceptance

- Packed units rejected at POS; sold units cannot resell.
- Cancelled page: restock session + history; no tracking cancel UI; API cancel_parcel_scan → 410.
- RTS: tracking required; optional honey+dept codes → rts/damaged pools.
