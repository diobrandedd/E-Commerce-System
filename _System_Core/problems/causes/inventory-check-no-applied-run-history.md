---
date: 2026-09-17
type: cause
area: inventory-check
tags: [cause]
---

# Cause: Merged audit confirms had no browseable history

Finalize/merge wrote `inventory_check_compare_runs` (+ variance lines) under the primary `event_id` only. History listed sessions, not applied runs; session Comparison had no run picker and secondary merge members could not see the confirm via `compare_list`.
