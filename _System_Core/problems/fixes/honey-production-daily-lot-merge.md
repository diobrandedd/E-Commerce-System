---
type: fix
tags: [fix]
---

# Fix: Shared daily production lot upsert for manual + scan stock-out

## What we changed

Manual and scan warehouse → production stock-outs share `honey_production_upsert_daily_lot()`. Same supplier/arrival/variant/day merges gallons onto one lot and writes stock-out events. Production list returns one row per lot. Dropdown labels include stock-out date. Optional consolidate tool cleans legacy same-day duplicates.

## Implementation

- `honey_production_daily_group_key` + `honey_production_upsert_daily_lot` in `honey_inventory.php`
- `honey_warehouse_stock_out` uses upsert (absorbs NULL-key legacy same-day rows)
- `honey_warehouse_barcode_stock_out` calls the same helper
- `production_list` drops packaging JOIN
- SPA option labels + Production guide copy
- `tools/consolidate_honey_production_daily_lots.php` (dry-run / `--apply`)

## Verify

1. Warehouse → manual stock out 5 gal Amber, then 3 gal same row same day → one Amber Production lot with 8 gal.
2. Scan stock-out same supplier/variant/day adds onto that lot.
3. Light vs Amber same supplier code → two lots.
4. Production lot dropdown shows each lot once; label includes date.

## Related problems

- [[records/2026-09-15_honey-production-lot-merge]]
