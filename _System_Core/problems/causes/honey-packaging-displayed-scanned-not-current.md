---
date: 2026-09-16
type: cause
status: documented
area: honey-packaging
tags: [cause]
---

# Cause: Packaging UI bound to scanned_* not current_*

`packaging_scan_report` returns both `current_*` and `scanned_*`. The Packaging page rendered only scan-event sums. `executive_edit` correctly wrote on-hand, so the UI and inventory truth diverged after Edit.
