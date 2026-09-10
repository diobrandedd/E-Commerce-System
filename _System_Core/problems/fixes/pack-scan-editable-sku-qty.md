---
type: fix
tags: [fix, pack-scan, sku, session]
---

# Fix: Editable Pack scan SKU quantity

## What we changed

- Draft pack sessions expose `pack_set_item_qty` for honey and dept SKU lines only (`sku_id` set, no unique `unit_id`).
- Quantity must be 1–5000; unique stickers stay remove-only.
- Pack scan UI: **Edit qty** on single-SKU groups and expanded SKU rows opens an `AppModal` to set absolute qty.
- Session card shows `total_qty` (sum of line quantities), not just line count.

## Implementation

- `honey_bottle_pack_session_set_item_qty()` in `includes/honey_bottle_barcodes.php`
- API action `pack_set_item_qty` on `bottle_barcodes.php`
- SPA client `bottleBarcodesApi.packSetItemQty` + `HoneyPackScanPage` edit modal

## Related problems

- [[records/2026-09-10_pack-scan-editable-sku-qty]]
