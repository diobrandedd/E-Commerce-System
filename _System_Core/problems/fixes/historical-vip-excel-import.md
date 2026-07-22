---
date: 2026-07-22
type: fix
status: implemented
area: fulfillment-historical-import
tags: [fix]
---

# Fix: Historical VIP Excel import (preview + commit)

Dedicated Order management tool:

1. Stash Excel → preview with `vip_order_list` parser
2. Tracking number = Parcel Name only (plain, no `VIP` / dashes); `ship_date` = last day of selected month; `status = shipped`; no inventory deduct
3. TN conflicts: side-by-side current vs uploaded; Keep current / Use uploaded (per row + bulk); RTS/packing/photo rows default Keep
4. Commit inserts new rows / overwrites historical-safe fields only

API: `historical_orders.php` actions `stash` | `preview` | `commit`.
