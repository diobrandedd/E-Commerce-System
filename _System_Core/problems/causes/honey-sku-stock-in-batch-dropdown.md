---
type: cause
tags: [cause, honey, barcode, packaging, sku]
---

# Cause: Honey SKU stock-in required an existing packaging-row selection

The honey SKU stock-in panel loaded existing packaging rows and required the operator to choose one. That does not fit SKU-based stock-in when the operator only has a custom batch number and supplier lookup is intentionally out of scope.

The backend accepted only a `packaging_row_id`, so the UI could not submit a new custom batch identifier directly.
