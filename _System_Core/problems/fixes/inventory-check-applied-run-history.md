---
date: 2026-09-17
type: fix
area: inventory-check
tags: [fix]
---

# Fix: Confirmed / merged audit history

- API `applied_runs_list` (view_all): stock-applied runs with session summaries
- Widen `compare_list` with `FIND_IN_SET` on `merged_event_ids`
- `compare_get` access via any merged member session
- History UI: Confirmed inventory updates + applied-run detail modal
- Session detail: comparison run picker + merged badge
