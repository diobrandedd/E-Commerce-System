---
type: cause
tags: [cause]
---

# Cause: Barcode requests had no OS toast

## What went wrong

Pending barcode counts were polled only for sidebar badges. There was no browser Notification, so Windows Action Center never showed a new request.

## Where in code

- Sidebar poll of `bottleBarcodesApi.pendingCount` / `deptPouchBarcodesApi.pendingCounts`
- No `Notification` usage in the SPA

## Related problems

- [[records/2026-08-18_barcode-request-desktop-notify]]
- [[records/2026-08-18_sidebar-barcode-request-badges]]

## Prevent

Desktop alerts need a user gesture for permission and an open tab — see [[003_Fragile_Edges#Desktop barcode request notifications]].
