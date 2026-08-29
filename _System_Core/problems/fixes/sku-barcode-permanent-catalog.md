---
date: 2026-08-26
area: inventory
---

# Fix: Permanent SKU barcode catalog

Store one short Code128 value per honey variant×size and dept product in `product_sku_barcodes`. Print N copies. Scan flows: SKU → enter qty (aggregate credit/debit); leftover unique 6-char stickers still resolve as qty 1. Never auto-overwrite barcode from selling price.
