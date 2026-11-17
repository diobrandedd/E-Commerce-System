---
status: approved
created: 2026-11-17
approved_by: chat
---

# Feature Spec: POS freebies + Sales "Freebies given"

## Problem

Staff cannot attach Paragis / Black Rice / Jar Cacao freebie samples to a walk-in POS sale. Pack Scan already deducts the freebie pool; POS and Sales do not show freebies given per receipt or as a period/channel breakdown.

## Goals

1. Scan FRB barcode or use **Add freebie** picker on unified POS; always ₱0; deduct freebie pool on checkout (cash + AR).
2. Persist freebies on the sale (`pos_receipt_freebies`); show them on Recent walk-in sales rows and thermal reprint.
3. Sales **Freebies given** card: Walk-in (POS) + Shipped (confirmed pack session `pool=freebie`), filterable by period and channel like dept products.

## Non-goals

- Charging for freebies; freebie-only carts; changing Pack Scan freebie flow; putting freebies into `pos_receipt_items` (would skew revenue reports).

## Acceptance

- Scan or picker adds freebie lines; checkout fails if only freebies or if pool stock is short.
- Receipt / Recent walk-in / Excel export show freebie lines.
- Full refund restocks freebie on_hand with an audit reason.
- Freebies given card respects period + walk_in / online / all.
