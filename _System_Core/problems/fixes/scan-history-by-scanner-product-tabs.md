---
date: 2026-09-07
type: fix
area: inventory-scan-history
tags: [fix]
---

# Fix: by_scanner + product tabs on finished scan history

1. `dept_pouch_scan_report` returns `by_scanner` (unique stickers + SKU qty, joined to `users`).
2. `dept_pouch_request_scan_log` unions SKU stock events with qty / scan_kind / scanned_by_name.
3. `honey_bottle_packaging_scan_report` returns `by_scanner`.
4. History modal: tabs Paragis / BRC / Cacao / Honey Comb / Honey bottles; **By person · all accounts** list; batch detail shows who scanned.
5. Honey packaging page shows the same by-person rollup.
