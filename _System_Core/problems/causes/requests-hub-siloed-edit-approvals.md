---
type: cause
tags: [cause, requests, inventory]
---

# Cause: Edit approvals were siloed outside Requests hub

## What went wrong

Delivery and inventory edit approvals lived on `/edit-requests` while executives used `/inventory/requests` for corrections. Approval workflows were split across pages and navigation duplicated for PM users.

## Where in code

- `InventoryRequestsPage.tsx` was inventory-only
- `EditRequestsPage.tsx` was a separate destination for edit approvals
- `Sidebar.tsx` had overlapping Requests navigation

## Related problems

- [[records/2026-06-05_unified-requests-hub]]

## Prevent

New approval flows should extend the Requests hub section model, not create extra top-level pages.
