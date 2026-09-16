---
date: 2026-09-16
type: cause
area: honey-production
tags: [cause]
---

# Cause: Production merge key included stock-out date

`daily_group_key` was `supplier|code|arrival|variant|stock_out_date`, so same supplier code + variant on a later day created a new `honey_production_rows` row instead of adding gallons to the open lot.
