---
date: 2026-09-16
type: fix
status: applied
area: honey-production
tags: [fix]
---

# Fix: Production lot `is_active` + scan select filter

1. Add `honey_production_rows.is_active` (default 1) via phase 83.
2. `executive_edit` on production accepts `is_active` + gallon qty (`current_only` / `set_exact`).
3. Production page: Edit lot → `ExecutiveRowEditModal` (Active checkbox + gallons).
4. Scan dropdown: only active lots with non-zero `current_qty` (OOS and inactive hidden; overdrawn kept).
5. Server rejects bottle stock-in / production stock-out when lot is inactive.
