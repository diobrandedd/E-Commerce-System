---
type: cause
tags: [cause, deliveries, performance]
---

# Cause: Delivery save waited on large upload plus extra refresh chain

## What went wrong

Done/Scan next was blocked by full-size photo upload and multiple sequential API calls (`upload_photo` → refresh → `ship_parcel` → refresh). PHP also read full upload bytes even when `client_hint` already identified tracking.

## Where in code

- `DeliveriesPage.tsx` save flow
- `includes/deliveries_upload.php` classification path

## Related problems

- [[records/2026-06-05_deliveries-save-latency]]

## Prevent

Use capture-time background upload with client-side compression and avoid redundant post-mutation refreshes.
