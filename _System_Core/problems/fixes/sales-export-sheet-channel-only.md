---
date: 2026-09-16
type: fix
area: sales-report
tags: [fix]
---

# Fix: Per-sheet Excel summary is one channel only

`honey_sales_export_write_summary_block($sheet, $title, $filterLabel, $summary, $channel)` writes only that channel’s total, RTS count, and by-size / by-variant / by-variant×size tables. Callers pass `$shippedSummary` + `'shipped'` or `$walkInSummary` + `'walk_in'`.
