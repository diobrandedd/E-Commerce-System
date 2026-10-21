---
date: 2026-10-21
type: fix
---

# Fix: Supplies visible to executives and assigned stock-out staff

1. Nav and route: `packaging.catalog` or `packaging.deduct` (not `packaging.view`).
2. Assigned staff (`packaging.deduct` without catalog) scan stock out from the page card. Report damage is a header button. They do not see the inventory table or history.
3. Report damage stays on `packaging.deduct` so assigned staff can record ruined supplies without the catalog.
