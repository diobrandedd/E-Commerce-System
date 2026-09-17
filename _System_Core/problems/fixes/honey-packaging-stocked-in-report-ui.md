---
date: 2026-09-17
type: fix
area: honey-packaging
tags: [fix]
---

# Fix: Packaging = stocked-in report UI

Raw Honey → Packaging now loads `packaging_scan_report` by scan date and shows Light/Amber/Dark **scanned-in** totals (expand for sizes + by person). Live on-hand stays on Products → Honey (`packaging_total_inventory` / POOL `current_*`). Pack/POS deducts unchanged.
