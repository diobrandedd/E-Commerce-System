---
date: 2026-10-30
type: fix
tags: [freebie, barcode]
---

# Fix: One permanent barcode per freebie product

`dept_raw_freebie_barcodes` holds one code each for `pt`, `brc`, and `jar_cacao` (seeded `FRB-PT`, `FRB-BRC`, `FRB-CACAO`). Codes must be unique against product SKU codes and unique bottle/pouch stickers, in both directions.

- **Stock in:** scanning the freebie code on that product's raw page with a quantity calls `dept_raw_record_freebie_create` (leaf down by `qty × settings grams`, freebie on-hand up). The + Freebies button is gone; the Freebies card opens history only.
- **Give out:** Pack scan adds a dept line with `pool = 'freebie'`. Confirm calls `dept_raw_record_freebie_give_out` and never touches finished stock or the sales log. Scanning more than on-hand is rejected.
- **Print:** each Settings row prints one full 8×20 sheet of that barcode. Labels show the grams at print time.
- **Check inventory:** scanning a freebie code adds to the existing `FREEBIE:{product_key}` line.
- Cancel restock refuses freebie codes. Barcode verify shows the product, grams, and on-hand.
