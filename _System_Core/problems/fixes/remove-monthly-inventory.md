---
date: 2026-11-13
type: fix
status: applied
area: inventory
tags: [fix]
---

# Fix: Delete Monthly inventory page and monthly-only APIs

Remove the SPA page, nav, route (redirect to Logs), and `inventory.monthly_count`. Delete seed production/packaging and `dept_raw_set_bags` API entry points. Strip `monthly_inventory` admin-password branches from honey/materials/rts mutations. Keep admin password for Check inventory confirm and leaf-pool executive set; keep `edit_log_list` for the finished-honey modal.
