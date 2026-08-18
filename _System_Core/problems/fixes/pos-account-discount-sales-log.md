---
type: fix
tags: [fix]
---

# Fix: Account POS, checkout discounts, Sales seller log

## What we changed

Walk-in POS is permission `pos.sell` (executives always). 10%/20% complete the sale immediately. Receipts store PIN **or** IMS user. Sales Recent walk-in sales shows Sold by / Sold to and filters by product.

## Implementation

- Catalog key `pos.sell`; `api_pos_require_session()` accepts PIN **or** `pos.sell`
- `pos_session_actor()` + `sold_by_user_id` (phase 65)
- Unified POS 10%/20% call checkout; IMS Exit returns to dashboard
- Sales list joins user/PIN name; `product` query filter on receipts

## Verify

1. Grant **Walk-in POS** on a staff account (or use executive). Open **POS** from the sidebar.
2. Scan items, Checkout → **10% off** — receipt prints with discount; no waiting overlay.
3. Sales → Recent walk-in sales shows **Sold by** (staff name) and **Sold to** (buyer). Tap Honey / Paragis / All products to filter the list.
4. PIN POS Exit still logs out. Staff POS Exit returns to Dashboard.

## Related problems

- [[records/2026-08-18_pos-account-discount-sales-log]]
