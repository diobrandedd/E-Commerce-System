---
date: 2026-06-18
type: problem-record
status: fixed
area: deliveries
tags: [cancelled, re-waybill, inventory]
---

# Problem: Cancelled parcels treated as opened returns (inventory restored, no re-waybill)

> Courier-cancelled boxes stay sealed and reusable — cancel must not revert deduct; re-waybill must accept `cancelled` status.

## Symptom

Ops marked courier-cancelled parcels on Deliveries → Cancelled expecting to re-waybill and re-ship the same sealed box. Backend reverted inventory on shipped cancel and re-waybill only allowed `on_hold`.

## Cause

→ [[causes/cancelled-confused-with-opened-return]]

## Fix

→ [[fixes/deliveries-cancel-scan-revert-and-log]] (updated semantics)

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/online_order_parcels.php` — cancel, ship idempotent deduct, rewaybill from cancelled |
| API | `api/v1/deliveries.php` — cancel message |
| SPA | `DeliveriesCancelledPage.tsx`, `DeliveriesOpsPanel.tsx` |
| Spec | [[specs/2026-06-17_fulfillment-packing-staging]] |
