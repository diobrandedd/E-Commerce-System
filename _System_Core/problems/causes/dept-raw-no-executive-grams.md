---
date: 2026-09-16
type: cause
status: documented
area: dept-raw
tags: [cause]
---

# Cause: Raw leaf pool had no day-to-day executive gram editor

`dept_raw_set_bags` existed only for Monthly Inventory (kg + admin password). `executive_edit` stage `dept` targets finished `dept_inventory_rows`, not leaf `stock_entries`. Raw pages showed grams but offered no Edit control.
