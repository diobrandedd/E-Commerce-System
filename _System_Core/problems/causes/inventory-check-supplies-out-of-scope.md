---
type: cause
---

# Cause: Inventory check excluded packaging materials

Earlier inventory-check specs listed packaging/supplies as out of scope. `inventory_check_scan` resolved freebie → SKU → unique stickers only. Shared `sku_barcode_lookup` also omitted PKG codes so pack-scan screens would not accept them.
