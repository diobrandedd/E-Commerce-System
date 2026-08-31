---
type: fix
area: fulfillment
tags: [dept-pouch, sku-barcode, requests]
---

# Fix: Generate an internal batch reference for static SKU requests

`dept_pouch_request_create()` now accepts a blank batch code and generates a
valid internal `SKU-{PRODUCT}-{timestamp}` reference for the legacy non-null
request column. Operators still request only a quantity and notes; printing
continues to use the configured permanent SKU barcode. Explicit batch codes,
when supplied by older callers, retain the existing validation.

No SQL or schema change is required.
