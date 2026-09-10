---
date: 2026-09-10
type: fix
---

# Fix: Accept suppliers.manage on Supplier Master price APIs

`api_require_permission('suppliers.manage', 'honey.executive_edit')` for:

- `supplier_dept_prices` / `supplier_dept_prices_save`
- `supplier_honey_prices` / `supplier_honey_prices_save`

SPA gates Add/Edit/activate and price expand on manage (or executive edit for prices).
