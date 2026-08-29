---
type: fix
status: fixed
area: honey-warehouse
tags: [fix, barcode, inventory, production]
---

# Fix: Scan supplier/date barcodes into daily Production

Added a permission-gated warehouse barcode request and print queue. Labels use the existing supplier/date code format such as `NF082826`, remain reusable across variants, and are separate from product SKU barcodes.

Warehouse stock-out now supports scan → candidate supplier/date/variant → gallons. The transaction consumes matching warehouse rows oldest-first, writes an immutable stock-out event for each source row, and upserts one supplier-aware daily Production row per stock-out date and variant. Manual stock-out and existing bottling flows remain available.
