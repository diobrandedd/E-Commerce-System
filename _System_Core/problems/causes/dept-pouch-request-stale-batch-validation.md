---
type: cause
area: fulfillment
tags: [dept-pouch, sku-barcode, requests]
---

# Cause: Static SKU request form still triggered legacy batch validation

The department pouch request UI was changed to request a quantity of identical
static SKU stickers and no longer sent a batch code. The shared request helper
still normalized and required the legacy operator-entered batch code before
inserting the request, so every blank submission failed before the configured
SKU could be used for printing.
