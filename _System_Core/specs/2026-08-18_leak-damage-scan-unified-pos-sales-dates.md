---
date: 2026-08-18
status: approved
area: inventory-pos-sales
---

# Spec: Leak/damage scan, Sales dates, unified POS

## Goal

1. **Leak/damage scan** — Staff pick Leaked or Damaged, scan any finished sticker (Honey + dept products). Remove from finished inventory, log history. No RTS pool.
2. **Sales dates** — Sales dashboard supports a single chosen date or a custom date range (not only Today/week presets).
3. **Unified POS** — One scan-first POS: auto-detect product from barcode, manual price per line, keep 10%/20% discount and buyer name.

## Leak/damage scan

- Products: all sticker products (honey bottles + Paragis + BRC + Jar Cacao + Honey Comb).
- Reason toggle before scan: `leaked` | `damaged`.
- Success: unit `in_stock` → status `damaged`, aggregate inventory −1, log row with reason.
- Failure: not in stock → **Not in inventory** (no mutation).
- Unified page at `/inventory/leak-damage`; retire Jar Cacao–only defect UI.

## Sales dates

- Period options: Today, Pick date (single day), Custom range, week/month/year/all.
- Backend uses existing `period=custom` with `from`/`to`.

## Unified POS

- `/pos` is scan-first; legacy category routes redirect to `/pos`.
- `unified_lookup` tries honey then dept.
- Manual `list_unit_price` per cart line required before checkout.
- Mixed honey + dept cart in one receipt; reuse unit consume helpers.

## Out of scope

- RTS damaged pool intake from leak scan.
- Reversing leak/damage scans.
- Catalog selling_price enforcement at POS (manual for now).
