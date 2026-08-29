---
type: cause
tags: [cause, honey, sku, barcode, packaging, reporting]
---

# Cause: Packaging scan details only read individual stickers

The Packaging row detail endpoint queried `honey_bottle_barcodes` and treated every result as one sticker. Quantity-based SKU stock-ins are stored in `sku_barcode_stock_events`, so the detail modal could show no entries even when the row total included SKU stock-in quantities.
