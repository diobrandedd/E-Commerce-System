---
type: cause
tags: [cause, sidebar, routing]
---

# Cause: Sidebar child route prefix matching activated sibling entry

## What went wrong

`NavLink` default matching treated `/deliveries/review` as matching `/deliveries`, so both “Add deliveries” and “Review batches” appeared active.

## Where in code

- `Sidebar.tsx` child nav rendering for deliveries group

## Related problems

- [[records/2026-06-05_deliveries-sidebar-active-state]]

## Prevent

Use `end: true` on sibling base routes that should only match exact path.
