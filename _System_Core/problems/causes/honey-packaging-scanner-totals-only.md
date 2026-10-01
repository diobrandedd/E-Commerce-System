---
date: 2026-10-01
type: cause
area: honey-packaging
tags: [cause]
---

# Cause: by_scanner grouped only by user

`honey_bottle_packaging_scan_report` summed `qty` per `scanned_by` and dropped variant/size even though the scan source SQL already had both.

→ Problem: [[records/2026-10-01_honey-packaging-scanner-variant-size]]
