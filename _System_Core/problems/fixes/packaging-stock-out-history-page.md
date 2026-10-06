---
date: 2026-10-06
type: fix
area: packaging-materials
---

# Fix: Packaging stock-out history page

- `material_packaging_stock_out_history` lists packaging `use:` entries with item, absolute qty, reason, actor, timestamps; filters `q` / `user_id` / date range; paginates; returns distinct `actors`.
- API `packaging_stock_out_history` gated by `packaging.view`.
- SPA: **History** button on Packaging Materials opens `PackagingStockOutHistoryModal` (not a separate sidebar route).
