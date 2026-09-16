---
type: cause
tags: [cause]
---

# Cause: Manual warehouse stock-out always inserted a new production row

## What went wrong

Scan stock-out already upserted by `daily_group_key`. Manual `honey_warehouse_stock_out()` always `INSERT`ed a new `honey_production_rows` row with no `stock_out_date` / `daily_group_key`. Part-by-part transfers therefore created many Production lots sharing the same supplier code. Separately, `production_list` LEFT JOINed packaging, so one lot with multiple bottling dates appeared multiple times in the lot dropdown.

## Where in code

- `honey_warehouse_stock_out()` in `includes/honey_inventory.php`
- `api_honey_production_list()` in `api/v1/honey.php`

## Related problems

- [[records/2026-09-15_honey-production-lot-merge]]

## Prevent

→ [[003_Fragile_Edges]] honey production daily lot merge bullet
