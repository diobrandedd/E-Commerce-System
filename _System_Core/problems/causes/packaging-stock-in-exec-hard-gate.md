---
date: 2026-11-17
type: cause
---

# Cause: Stock-in helper required executive role

`material_inventory_role_can_stock_in_packaging()` required `packaging.stock_in` **and** `ims_is_executive()`. Supplies route also omitted `packaging.stock_in`, so non-exec assignees could not open the page for lot receive.
