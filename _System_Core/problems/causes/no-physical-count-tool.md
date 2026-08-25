# Cause: no read-only physical count tool

Every finished unit carries a barcode, but all existing scan flows mutate stock:

| Flow | Effect |
|------|--------|
| Packaging scan-in | adds to packaging inventory |
| Pack scan | reserves / packs units |
| Ship | deducts |
| Leak / damage | removes |

There was no way to say "this unit is physically on the shelf" without changing something, so a physical count could not be recorded, and nothing existed to compare a count against system stock.

Fix: [[fixes/inventory-check-daily-count]]
