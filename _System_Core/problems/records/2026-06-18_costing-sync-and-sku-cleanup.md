---
date: 2026-06-18
type: problem-record
status: fixed
area: costing-database
tags: [costing, cleanup, migration]
---

# Problem: Legacy packaging SKUs clutter DB; costing not synced to current prices

## Symptom

Database dump had deactivated phase-30 parcel SKUs, superseded cushion catalog rows, and generic bubble with wrong `pool_yield_qty`. Product baselines drifted from `stocks.current_price` after stock-in lots. User expected to **only update prices** in Supplier Master / Packaging Materials.

## Cause

No table-level dead code (all 55 tables referenced in PHP). Data-level legacy `stocks` rows remained active. `product_formula.price_at_creation` snapshots were stale. No one-shot sync from supplier prices + packaging WAC.

## Fix

- `migration_phase39_cleanup_legacy_packaging.sql` — deactivate legacy SKUs, fix generic bubble yield, purge tier lines on inactive stocks.
- `tools/sync_all_costing_from_prices.php` — supplier prices → stocks, packaging lot WAC, formula snapshots, `baseline_cost` recalc. Parcel packaging already live from `stocks.current_price` (+ generic bubble roll for sized lines).

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase39_cleanup_legacy_packaging.sql` |
| Tool | `tools/sync_all_costing_from_prices.php` |

## Deploy

```bash
mysql -u root rts_system < sql/migration_phase39_cleanup_legacy_packaging.sql
php tools/sync_all_costing_from_prices.php
```

## AI rules

Do **not** drop DB tables without a full code reference audit — all current tables are in use. Clean inactive `stocks` rows instead.
