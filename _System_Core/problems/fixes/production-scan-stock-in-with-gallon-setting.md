---
date: 2026-09-04
type: fix
status: implemented
area: honey-production
tags: [fix]
---

# Fix: Production scan stock-in + settings ml/gal

1. `app_settings` + `honey.ml_per_gallon` (default 3785.411784) editable in System Settings.
2. `honey_production_bottle_stock_in` — select production lot, credit packaging bottles, deduct gallons via settings ml/gal, deduct empty-bottle materials, credit label requests.
3. `scan_stock_in` accepts `production_row_id` for SKU and unique stickers.
4. Production SPA panel with lot select, continuous SKU +1, gallon preview; Packaging copy points operators to Production for lot bottling.
