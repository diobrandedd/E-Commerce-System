---
date: 2026-10-21
type: fix
---

# Fix: Supplies visible to executives and assigned stock-out staff

1. Nav and route: `packaging.catalog` or `packaging.deduct` (not `packaging.view`).
2. Assigned staff (`packaging.deduct` without catalog) land on Scan stock out only.
3. Report damage stays on the executive catalog and requires `packaging.catalog`.
