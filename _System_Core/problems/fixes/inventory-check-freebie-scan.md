---
date: 2026-11-05
type: fix
status: applied
area: inventory-check
tags: [fix]
---

# Fix: Freebie barcodes use the same scan → qty → confirm path as SKUs

In `InventoryCheckPage` `onAddScan`, treat `lookup_kind === "freebie"` like SKU (pending quantity panel, then `commitScan`). Remove the manual Freebie tab and client `scanFreebie` helper. Compare/apply already use freebie on-hand.
