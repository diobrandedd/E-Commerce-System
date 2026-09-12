---
date: 2026-09-12
type: fix
status: implemented
area: inventory
tags: [fix, monthly-inventory, admin-password]
---

# Fix: Administrator password for Monthly inventory overrides

## Behavior

1. Keep **Monthly inventory** in sidebar + route (`inventory.monthly_count`).
2. Store a hashed **administrator override password** in `app_settings` (`security.admin_override_password_hash`), set/changed by executives under **System Settings**.
3. Monthly inventory stock mutations send `admin_password` (+ `monthly_inventory` / `log_source=monthly_inventory` where needed). API rejects missing/wrong password via `api_require_admin_override_password()`.

## Gates

| Action | When password required |
|--------|------------------------|
| `honey.executive_edit` | `monthly_inventory` or `log_source=monthly_inventory` |
| `warehouse_stock_in` | monthly flag |
| `seed_*` / `dept_raw_set_bags` | always (Monthly-only callers) |
| `materials.set_on_hand` / `stock_in_lot` | monthly flag / note |
| `rts.inventory_set_*` | `monthly_inventory` flag only (RTS page unchanged) |

## SPA

- Settings: Administrator password card
- Monthly inventory: `AdminOverridePasswordModal` before each override mutation
