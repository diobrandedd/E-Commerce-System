---
type: fix
tags: [fix, inventory-check, history, modal]
---

# Fix: Viewable Check inventory history sessions

## What we changed

- History rows (desktop **View**, phone **View session**) open `InventoryCheckHistoryDetailModal`.
- Modal tabs: **Counted** (product summary + per-row **Log**), **Comparison** (latest saved compare), **Scan log** (paginated scans).
- Reuses existing `summary`, `compare_list` / `compare_get`, `scan_log`, and `InventoryCheckScanLogModal`.

## Related problems

- [[records/2026-09-10_inventory-check-history-detail]]
