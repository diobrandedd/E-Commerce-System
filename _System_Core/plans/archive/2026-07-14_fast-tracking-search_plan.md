---
title: Fast tracking search
status: implementing
approved: true
tags: [performance, deliveries, rts, parcels]
---

# Plan: Fast tracking search (Deliveries / RTS / Online parcels)

Approved via chat (implement attached plan). No schema migration.

## Approach

1. SPA `useDebouncedValue` (~300ms) on Deliveries review, RTS records/swapped/leaks, Online parcels `q`.
2. PHP `includes/tracking_search.php`: TN-shaped queries (≥8 chars, no spaces) use `=` / prefix `LIKE 'q%'`; soft text keeps contains `LIKE '%q%'`.
3. Online parcels: live search caps `per_page` at 100 while `q` is set.

## Files

- `app/src/app/hooks/useDebouncedValue.ts`
- `includes/tracking_search.php`
- `api/v1/deliveries.php` review_list
- `includes/rts_case_handlers.php` list + disposition list
- `includes/online_order_parcels.php` list_query_parts
- SPA pages: DeliveriesReview, RTSRecords, RTSSwapped, RTSLeaks, OnlineParcels
