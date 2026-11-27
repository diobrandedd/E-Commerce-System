---
date: 2026-11-24
type: fix
area: honey-packaging
---

# Fix: Packaging stock-in line undo + HR approval

1. API `packaging_scan_line_undo` — executive; HR manager applies now, others create pending override approval (was admin password until 2026-11-26).
2. `honey_bottle_packaging_scan_line_undo` reverses matching sticker units (must stay packaging `in_stock`) and SKU honey events for that date/person/variant/size; decrements variant POOL; credits production gallons; activity log. Stale `expected_qty` fails the approval request.
3. Packaging SPA: Undo per person size line (specific date only) → `OverrideConfirmModal`. See [[fixes/hr-manager-approvals]].
