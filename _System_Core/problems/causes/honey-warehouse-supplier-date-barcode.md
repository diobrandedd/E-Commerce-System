---
type: cause
status: fixed
area: honey-warehouse
tags: [cause, barcode, inventory]
---

# Cause: Warehouse stock had no scan identity

Honey warehouse rows already stored the supplier code and arrival date, but those fields were only displayed on manual stock-in/stock-out rows. There was no request/print history for supplier/date labels, no scanner lookup that could resolve the variant, and no immutable event record when multiple source rows were consumed.

Supplier initials plus date are not guaranteed globally unique, so lookup must return candidates instead of silently selecting a supplier. Daily Production aggregation also needs a supplier-aware key plus source events.
