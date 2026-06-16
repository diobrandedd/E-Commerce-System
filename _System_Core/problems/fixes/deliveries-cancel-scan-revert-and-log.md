# Fix Pattern: Deliveries cancel scan with shipped revert and audit log

## Summary

Scanner cancellation is handled as a first-class deliveries action, not a direct parcel status edit.

## Pattern

1. Add `cancel_parcel_scan` in `api/v1/deliveries.php` with the same auth + CSRF gates as `ship_parcel`.
2. Use shared parcel domain function (`online_parcels_cancel`) to enforce transitions:
   - allowed: `packing`, `shipped`
   - `shipped` must call `honey_parcel_revert_deduct()` before status change
3. Write one audit row per cancellation in `delivery_cancel_events` (previous status, reverted flag, source, user, timestamp).
4. Expose read API `cancel_log_list` and render a review table in Deliveries Review.

## Why

Directly setting `online_order_parcels.status='cancelled'` without shipped-deduct reversal leaves inventory wrong and removes operational traceability.

## Files

- `includes/online_order_parcels.php`
- `api/v1/deliveries.php`
- `app/src/app/lib/deliveriesApi.ts`
- `app/src/app/pages/deliveries/DeliveriesPage.tsx`
- `app/src/app/pages/deliveries/DeliveriesReviewPage.tsx`
- `sql/migration_phase32_delivery_cancel_events.sql`
