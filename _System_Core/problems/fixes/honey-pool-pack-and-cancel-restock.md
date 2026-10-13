---
type: fix
tags: [fix]
---

# Fix: Pack and cancel restock use the variant honey pool

## What we changed

Pack confirm (unique stickers and SKU packaging lines) deducts the Light/Amber/Dark pool. Deliveries → Cancelled restock credits that same pool. Inventory check compare and apply read and write the pool only. RTS sellable is unchanged.

## Verify

1. Note Products → Honey for one variant/size.
2. Pack-scan that bottle and confirm — the pool count drops by the scanned qty.
3. Cancelled restock the same barcode — the pool count returns.
4. Inventory check system qty for that size matches the Honey tab.

## Related problems

- [[records/2026-10-10_honey-pool-pack-cancel]]
