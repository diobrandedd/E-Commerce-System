# Problem: Honey comb had parcel packaging only — no manufacturing BOM

> Production = jar w/ cap + sticker + raw comb. Parcel = Excel row 13 (`honey_comb_solo`). Spec: [[specs/2026-06-18_honey-comb-production-packaging]].

## Problem

Ops packs honey comb into a dedicated jar (cap included) plus sticker for walk-in and online. System only had fulfillment tier `honey_comb_solo` (cushion/bubble/cardboard). No `dept_product_key`, formula, inventory, POS, or parcel COGS for comb product.

## Cause

Parcel spreadsheet covers ship materials only; manufacturing packaging was never modeled. `suppliers.category` ENUM lacked `honey_comb` → insert truncated (phase 43).

## Fix

- `migration_phase42_honey_comb_product.sql` — stocks, product, formula, `dept_inventory_rows`
- `honey_comb` in `dept_product_catalog()` — raw + jar + sticker BOM
- Produce API `dept_comb_produce`, inventory/POS/costing UI
- Parcel COGS via `$addDept('honey_comb')`; reference addon `honey_comb_solo`
- Parser returns `product_key: honey_comb` for ship deduct

## Files

- `includes/dept_products.php`, `dept_production.php`, `parcel_pack_costing.php`, `honey_parcel_item_parser.php`
- `app/src/app/lib/deptProducts.ts`, `inventoryRoutes.ts`, `costingProducts.ts`, POS/inventory pages
- `sql/migration_phase42_honey_comb_product.sql`
- `sql/migration_phase43_suppliers_honey_comb_category.sql`
