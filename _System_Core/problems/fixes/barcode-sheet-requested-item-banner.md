---
type: fix
area: fulfillment
tags: [barcode, labels, sku]
---

# Fix: Use requested item metadata for barcode sheet banners

The shared barcode renderer now derives banner text from the existing SKU
metadata. Honey groups use size plus variant, such as `350ml – Amber`, while
department groups use the product name, such as `Black Rice Coffee`. The
three-part label cell and exact Code128 payload remain unchanged.

Explicit custom banners are still supported. Generic `BATCH A/B` values from
legacy callers are replaced with product metadata. Honey supplier/date labels
without product metadata retain their plain legacy layout.

No SQL or database change is required.
