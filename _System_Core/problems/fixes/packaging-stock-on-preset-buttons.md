---
type: fix
tags: [fix, csr, inventory]
---

# Fix: Show packaging stock on variant preset buttons

## What we changed

`packaging_stock` API + `useHoneyPackagingStock()` — each preset shows `{n} left` from `honey_packaging_rows` (batch bottled only). Wired in Shopee, VIP, Order Remarks carousels. Fixed `cn is not defined` in ItemResolverCarousel.

## Verify

Open Shopee/VIP carousel — presets show stock counts; 2L/3L combos show min 1L stock.

## Related problems

- [[records/2026-06-05_csr-variant-picker-stock]]
