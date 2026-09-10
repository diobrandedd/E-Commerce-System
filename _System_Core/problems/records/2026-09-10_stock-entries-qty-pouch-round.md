---
date: 2026-09-10
type: problem-record
status: fixed
area: paragis-bag-pool
tags: [problem, inventory, precision]
---

# Problem: Paragis pouch scan deducted 50 g instead of 48 g

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

After scanning one pouch, finished count rose by 1 but bag pool dropped by **50 g / 0.05 bags** (e.g. 178,930 → 178,880) instead of **48 g / 0.048 bags**.

## Cause

→ [[causes/stock-entries-qty-two-decimal-round]]

## Fix

→ [[fixes/stock-entries-qty-decimal-14-6]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase78_stock_entries_qty_precision.sql` |
| Deduct path | `includes/dept_inventory.php` (`dept_raw_deduct_bags_allow_negative`) |
| Scan caller | `includes/dept_pouch_barcodes.php` |
| Fragile | [[003_Fragile_Edges]] — stock_entries.qty precision |

## Deploy

- Run `sql/migration_phase78_stock_entries_qty_precision.sql` on each environment
- Past pouch scans that already wrote `0.05` stay as-is; only new scans get `0.048`
- PHP only (no SPA rebuild required for the column change)

## Links

- **Fragile edges:** [[003_Fragile_Edges]]
- **Spec:** [[specs/2026-09-10_paragis-bag-pool-pouch-scan]]
- **Related:** [[problems/records/2026-09-10_paragis-bag-pool-pouch-scan]]
