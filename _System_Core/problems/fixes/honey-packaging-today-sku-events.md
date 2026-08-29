---
type: fix
tags: [fix, honey, sku, barcode, packaging, reporting]
---

# Fix: Include SKU quantities in Packaging scan reports

The Packaging report now unions individual bottle sticker scans with honey SKU stock-in events. Date options, batch rows, variant totals, and overall totals all use the combined event source, so a quantity-based SKU stock-in appears under its scan date, including Today.
