---
type: cause
tags: [cause, honey, sku, barcode, packaging, reporting]
---

# Cause: Packaging scan report excluded SKU stock-in events

The Packaging report filtered only `honey_bottle_barcodes.scanned_at`, which represents individual sticker scans. Quantity-based honey SKU stock-ins are recorded in `sku_barcode_stock_events`, so the report could show older sticker rows under All dates while omitting a current SKU stock-in from Today.
