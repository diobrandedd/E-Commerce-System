---
date: 2026-11-24
type: fix
area: honey-packaging
---

# Fix: Packaging stock-in line undo + admin password

1. API `packaging_scan_line_undo` — executive + `api_require_admin_override_password`.
2. `honey_bottle_packaging_scan_line_undo` reverses matching sticker units (must stay packaging `in_stock`) and SKU honey events for that date/person/variant/size; decrements variant POOL; credits production gallons; activity log.
3. Packaging SPA: Undo per person size line (specific date only) → AppModal with administrator password.
