# Fix: Packaging barcodes + scan stock-out + level %

1. Add `stocks.barcode` (phase 90), backfill `PKG######`.
2. Enrich list with `last_lot_qty` / `level_pct` from latest `lot:%` entry.
3. A4 print-all via SKU packer; name **under** barcode.
4. Scan modal → lookup → qty/reason → `packaging_deduct`.
5. Stock-in executive-only; access roster on page.
6. No-op bottling + skip dept produce packaging deduct lines.

See [[fixes/packaging-inventory-roles]] and [[specs/2026-10-06_packaging-barcode-stock-out]].
