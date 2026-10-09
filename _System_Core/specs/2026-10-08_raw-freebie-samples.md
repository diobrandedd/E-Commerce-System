---
status: approved
created: 2026-10-08
approved_by: chat
---

# Feature Spec: Raw materials freebie samples

## Problem

Ops give small sample pouches (Dry Paragis, Black Rice, Cacao) without a tracked pool. Leaf is used but there is no freebie on-hand count next to finished inventory, and no history of creates / give-outs.

## Goals

1. Configurable **grams per freebie sample** per product in Settings (`pt` / `brc` / `jar_cacao`).
2. **Create freebie:** deduct raw leaf by `(qty × grams) → bags/kg`, credit **Freebie on-hand** by `qty`.
3. **Give out freebie:** deduct Freebie on-hand only.
4. Metric card **Freebies** beside Finished pouches/pcs on those raw pages.
5. Modal **Create | Give out | History** via **+ Freebies**.

## Non-goals

- Barcode stickers for freebie samples
- POS / sellable finished mix-in
- Honey freebies

## Acceptance

- Settings save freebie grams; Create blocked if grams `<= 0`.
- Create lowers leaf pool and raises Freebies card; Give out lowers Freebies; History lists both.
- Honey Comb / non–bag-pool tabs have no Freebies control.
