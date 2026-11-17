---
date: 2026-11-17
type: fix
---

# Fix: `packaging.stock_in` is permission-only

- Helper returns `ims_user_can(..., 'packaging.stock_in')` with no role check.
- `material_inventory_role_can_view` includes stock_in so catalog/detail load.
- Supplies route/nav guard accepts catalog | deduct | stock_in.
- Scan-only mode skips users who also have stock_in (they need the item list).
