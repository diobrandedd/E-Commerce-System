---
date: 2026-09-30
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Production bottle stock-in overdrew one lot while other lots still had gallons

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

On Production gallons (e.g. Amber with multiple supplier lots), finished bottle/SKU stock-in kept deducting from a single lot until it went negative, even when other lots of the same variant still showed gallons.

## Cause

→ [[causes/honey-production-fifo-skipped-positive-lots]]

## Fix

→ [[fixes/honey-production-fifo-drain-all-positive]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_inventory.php` (`honey_production_fifo_deduct_gallons`, `honey_production_variant_summary`) |
| Related | [[generated/modules/honey_inventory]] |
| Callers | `api/v1/bottle_barcodes.php` `scan_stock_in`; `honey_packaging_pool_stock_in` |

## Deploy

- PHP only — hard refresh SPA not required for deduct logic; production list sort improves after refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory / stock truth]]
- **Spec:** [[specs/2026-09-17_honey-packaging-pool-no-lots]]

## AI rules (optional)

Never overdraw a production lot while any same-variant lot still has `current_qty > 0`. Inactive lots with stock must be drained before overdraw.
