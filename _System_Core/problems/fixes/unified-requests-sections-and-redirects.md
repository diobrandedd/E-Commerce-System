---
type: fix
tags: [fix, requests, inventory]
---

# Fix: Unified Requests hub with sectioned approvals

## What we changed

Requests became a single executive hub with sections for inventory corrections, edit requests, and POS discount approvals.

## Implementation

- Added reusable `EditRequestsSection.tsx` and edit request API helpers
- Expanded `InventoryRequestsPage.tsx` into a sectioned approvals page
- Redirected executive/PM `/edit-requests` to `/inventory/requests#edit-requests-section`
- Removed duplicate PM sidebar nav entry

## Verify

1. Executive opens `/inventory/requests` and sees all approval sections
2. PM “My requests” from deliveries lands on `#edit-requests-section`
3. No duplicate Edit Requests item in PM sidebar

## Related problems

- [[records/2026-06-05_unified-requests-hub]]
