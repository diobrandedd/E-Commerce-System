# Packaging pool yield + bootstrap

## Pattern

- **`stocks.production_deduct`** — `1` per-unit (deduct on produce/bottling); `0` pool-only.
- **`stocks.pool_yield_qty`** — rough units per roll/spool for costing; executive-editable.
- **Costing** — `api_costing_formula_current_unit_sql` returns `current_price / pool_yield_qty` for pool lines.
- **PT produce** — deduct leaf + pouch only; yarn/paper on formula for cost only.
- **Catalog** — `migration_phase27_packaging_bootstrap.sql` seeds SKUs, formulas, 12 honey products.

## Do not

- Auto-deduct pool packaging on production.
- Use `PT tea bag sachet` on formula when paper roll is canonical.

## Spec

[[specs/2026-06-05_packaging-production-deduct]]
