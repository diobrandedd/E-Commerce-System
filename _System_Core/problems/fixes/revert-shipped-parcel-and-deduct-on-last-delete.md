---
type: fix
tags: [fix, deliveries, parcel-state]
---

# Fix: Revert shipped parcel and stock when last delivery item is deleted

## What we changed

If the last delivery row/photo for a parcel is removed, shipping status is reverted to `packing` and packaging deduction is restored.

## Implementation

- Added `honey_parcel_revert_deduct()` for stock + log rollback
- Added `deliveries_revert_parcel_to_packing_if_shipped()`
- Updated `deliveries_delete_item_by_id()` to run rollback only when no remaining delivery items exist
- Added orphan repair on parcel lookup for previously broken rows

## Verify

1. Delete a review-batch row
2. Rescan same tracking
3. Parcel can be photographed/shipped again (no “already shipped” block)

## Related problems

- [[records/2026-06-05_deliveries-delete-revert-ship]]
