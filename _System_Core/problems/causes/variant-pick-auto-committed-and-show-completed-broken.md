---
type: cause
tags: [cause, csr, shopee-spx, vip]
---

# Cause: Variant picks auto-committed; Show completed checkbox broken on VIP

## What went wrong

Shopee/VIP carousels could save wrong variant without confirm step. VIP `showCompleted` was hardcoded false with no-op handler. `trim is not a function` when API sent non-string items.

## Related problems

- [[records/2026-06-04_csr-variant-resolution-ux]]
