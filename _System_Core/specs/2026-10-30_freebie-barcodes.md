# Freebie barcodes

**Date:** 2026-10-30
**Status:** approved (chat 2026-10-30)
**Supersedes:** the "no barcodes" non-goal in [[specs/2026-10-08_raw-freebie-samples]]

## Goal

One permanent barcode per freebie product (Paragis, Black Rice, Jar Cacao) so freebies move through the same scan flows as finished stock:

- **Stock in** by scanning the freebie barcode on that product's raw page and entering a quantity. Deducts raw leaf (`qty × settings grams`) and credits freebie on-hand.
- **Give out** by scanning the freebie barcode in Pack scan. Deducts freebie on-hand only.
- **Print** all three freebie stickers at once from Settings, with a copies count.

## Rules

- Grams stay on the existing Settings BOM fields (`pt.freebie_grams`, `brc.freebie_grams`, `jar_cacao.freebie_grams`). The barcode identifies the product only. Printed labels show the grams at print time.
- Freebie codes live in their own table (`dept_raw_freebie_barcodes`), never in `product_sku_barcodes`. POS, RTS stock-in, and leak/damage read that table and treat non-honey SKUs as finished stock, so a freebie code there could be sold or deducted from the wrong pool.
- Codes are globally unique across freebie codes, product SKU codes, and unique bottle/pouch stickers.
- The manual **+ Freebies** button is removed. History stays on the Freebies card.
- Honey Comb has no freebies.

## Out of scope

POS sale of freebies; restocking freebies from cancelled parcels; changing grams per sample after stickers are printed.
