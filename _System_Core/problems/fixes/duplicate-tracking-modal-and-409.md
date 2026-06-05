---
type: fix
tags: [fix, online-orders, tracking]
---

# Fix: Duplicate tracking validation + modal on 409

## What we changed

`online_orders_assert_no_duplicate_tracking()` on append/resolve/partner parse. HTTP 409 + `duplicates[]`. `DuplicateTrackingModal` + shared hook in Partner/VIP/Shopee steps. VIP removed double lines_append.

## Related problems

- [[records/2026-06-05_duplicate-tracking-prevention]]
