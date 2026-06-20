---
type: cause
tags: [cause, deliveries, parcel-state]
---

# Cause: Delete removed photo row but not shipped parcel state

## What went wrong

Deleting a delivery item removed `delivery_batch_items` and photo file, but parcel shipping status and inventory deduction remained. Rescanning then failed as “already shipped.”

## Where in code

- `deliveries_delete_item_by_id()` in `includes/delivery_edit_requests.php`
- Parcel status and deduct lines in `online_order_parcels` + `online_parcel_deduct_lines`

## Related problems

- [[records/2026-06-05_deliveries-delete-revert-ship]]

## Prevent

When deleting the last delivery photo for a parcel, always reconcile parcel status and deduct lines.
