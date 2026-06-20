---
type: fix
tags: [fix, csr, shopee-spx, vip]
---

# Fix: Confirm + review + edit variant resolution flow

## What we changed

- Shopee: "Is this correct?" before save; review phase; `confirmed: true` required
- VIP: fixed Show completed; Edit choice via `onClearResolution`
- `honeyItemLineText()` for safe string normalization

## Related problems

- [[records/2026-06-04_csr-variant-resolution-ux]]
