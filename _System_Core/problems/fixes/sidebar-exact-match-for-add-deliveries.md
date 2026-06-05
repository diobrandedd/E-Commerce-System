---
type: fix
tags: [fix, sidebar, routing]
---

# Fix: Exact-match Add deliveries sidebar link

## What we changed

Added support for per-child `end` behavior and set `Add deliveries` to exact-match only.

## Implementation

- Extended `NavLinkChild` with `end?: boolean`
- Passed `end={child.end}` to child `NavLink`
- Set deliveries child config to `{ to: "/deliveries", end: true }`

## Verify

1. Visit `/deliveries` → only Add deliveries is active
2. Visit `/deliveries/review` → only Review batches is active

## Related problems

- [[records/2026-06-05_deliveries-sidebar-active-state]]
