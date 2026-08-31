---
type: cause
area: fulfillment
tags: [barcode, labels, sku]
---

# Cause: Barcode sheets used a generic batch banner

The SKU and RTS print flows supplied generic `BATCH A` / `BATCH B` banner
text even though each group already had product metadata. This made a printed
sheet harder to identify when requests were for different sizes, variants, or
named department products.
