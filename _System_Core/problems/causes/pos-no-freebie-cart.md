---
date: 2026-11-17
type: cause
---

# Cause: POS lookup and cart only handled honey/dept paid lines

`pos_unified_lookup` stopped at SKU + unique stickers. `pos_unified_build_cart_lines` required `product_id` and a positive price. Freebie give-outs existed only on Pack Scan confirm and manual honey API.
