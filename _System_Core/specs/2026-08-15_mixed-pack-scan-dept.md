---
date: 2026-08-15
status: approved
area: pack-scan
---

# Spec: Mixed pack scan (honey + dept pouches/jars)

## Goal

One Pack scan session accepts honey bottles **and** Paragis / Black Rice / Cacao / Honey Comb barcodes in any order. Confirm deducts honey packaging stock and finished dept stock for each scanned unit.

## In scope

- Companion table for dept pack-session items (keep existing honey items FKs).
- Pack scan: honey lookup first, then dept pouch barcode.
- Pack confirm: mark dept units `packed` + `dept_inventory_deduct(1)` per unit.
- Session payload: unified items / grouped / by_batch with `kind` + product label.
- History: optional `product_key` filter (`all` | `honey` | `pt` | `brc` | `jar_cacao` | `honey_comb`); batch search includes dept batch codes.
- SPA copy + confirm summary for mixed products.

## Out of scope

- Deducting WIP/raw or fulfillment packaging.
- Changing POS or ship deduct paths.
- Restock on discard after confirm (confirmed stays final).

## Acceptance

1. Scan honey → Paragis → Cacao → BRC in one draft session → Confirm.
2. Honey packaging qty and each dept finished `current_qty` drop by scanned counts.
3. History lists the session; product filter shows only sessions containing that product.
4. Unknown / not-in-stock / already packed / duplicate-in-session behave like honey today.
