---
type: fix
tags: [fix, shopee-spx]
---

# Fix: Shopee packing list Excel + PDF import path

## What we changed

Added packing list mode: Excel items from `product_info`, PDF names by tracking, `order_sn` → master Order No. B2T1/LAD variant rules + carousel for free 350ml.

## Implementation

- `shopee_spx_product_info.php`, `shopee_spx_packing_list.php`
- `spx_import_packing_list` API action
- `ShopeeSpxRunStep.tsx` mode toggle

## Verify

Import Packing List.Standard Local xlsx + PDFs → variant carousel → master Excel has Order No. and Name columns filled.

## Related problems

- [[records/2026-06-05_shopee-spx-packing-list-import]]
