---
type: fix
tags: [fix, honey, barcode, packaging, sku]
---

# Fix: Honey SKU stock-in uses a custom batch number

The honey bottle SKU stock-in flow now uses a free-text custom batch number instead of an existing packaging-row dropdown. The API creates or reuses a custom packaging row for the scanned SKU variant, using the typed batch code without supplier-master validation.

Unique legacy bottle stickers still use the batch metadata encoded in their generated barcode and do not require the custom field.
