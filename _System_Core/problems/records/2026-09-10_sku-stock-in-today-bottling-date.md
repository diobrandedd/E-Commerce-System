---
date: 2026-09-10
type: problem-record
status: fixed
area: honey-packaging
tags: [honey, packaging, sku, bottling-date, stock-in]
---

# Problem: SKU stock-in still credited older packaging lots

> Follow-on to [[2026-09-10_packaging-bottling-date-unique]] — unique stickers remapped to today; SKU did not.

## Symptom

Picking an older packaging batch when scanning a non-unique honey SKU credited that older `packaging_row_id`, so qty did not land on today’s `(batch_no, variant, bottling_date)` lot.

## Cause

`sku_barcode_honey_stock_in` adjusted aggregates on the caller-supplied row id without calling `honey_ensure_packaging_row_for_stock_in`.

## Fix

→ [[fixes/packaging-unique-batch-variant-date]] (SKU path now remaps like unique stickers)

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/sku_barcodes.php` (`sku_barcode_honey_stock_in`) |
| Related | `includes/honey_inventory.php` (production stock-in returns `bottling_date`) |
| Docs | [[003_Fragile_Edges#Honey packaging batch_no uniqueness]] |

## Deploy

- PHP only; regenerate memory bank
