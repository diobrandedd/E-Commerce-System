---
type: fix
tags: [fix, spa, modals]
---

# Fix: System-wide AppModal standard

## What we changed

Introduced `AppModal` + `AppModalHeader/Body/Footer` with consistent backdrop, motion, and `useReducedMotion()`. Migrated pages and modals across exec, honey, inventory, deliveries, CSR.

## Verify

Open modals on costing, honey pages, deliveries QR scanner, CSR duplicate tracking — consistent look and backdrop.

## Related problems

- [[records/2026-06-05_app-modal-standard]]
