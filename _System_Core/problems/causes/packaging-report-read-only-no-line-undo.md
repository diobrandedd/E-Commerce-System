---
date: 2026-11-24
type: cause
area: honey-packaging
---

# Cause: Packaging scan report had no reverse path

Gross stock-in history (`honey_bottle_barcodes.scanned_at` ∪ `sku_barcode_stock_events`) was intentionally read-only on Packaging. Live finished qty lived on the variant POOL / Products Edit. There was no atomic undo for a person + variant + size + day line, so mistaken stock-ins left permanent report rows unless stickers/events were hand-edited.
