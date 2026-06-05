---
type: cause
tags: [cause, shopee-spx]
---

# Cause: Shopee PDF-only path lacked packing list order_no and structured items

## What went wrong

CSR had Shopee packing list Excel with `order_sn`, `product_info`, and `tracking_number` but no import path. PDF-only extraction missed Order No. and reliable item lines from `product_info` blobs.

## Related problems

- [[records/2026-06-05_shopee-spx-packing-list-import]]
