# Packaging pool yield + bootstrap (phase 27)

## Problem

Production auto-deducted all packaging formula lines including rolls/seals. Catalog and honey costing SKUs were incomplete. Pool materials needed yield-based costing without per-unit deduct.

## Cause

Phase 26 treated every `product_formula` packaging line as operationally deductible. No `production_deduct` or `pool_yield_qty` on `stocks`.

## Fix

- Migration `migration_phase27_packaging_bootstrap.sql`: columns + full SKU/formula seed + 12 honey products.
- Pool costing: `(qty_per_unit / pool_yield_qty) × roll_WAC` in `_costing_helpers.php`.
- `dept_production.php`: skip `production_deduct = 0`; preview splits deduct vs cost-only.
- Honey bottling deducts size-specific bottle + sticker only.
- SPA: Packaging pool badges, yield edit, production preview panels.

## Files

- `sql/migration_phase27_packaging_bootstrap.sql`
- `includes/catalog_bootstrap.php`
- `includes/dept_production.php`, `includes/material_inventory.php`, `includes/honey_inventory.php`
- `api/v1/_costing_helpers.php`, `api/v1/materials.php`
- `app/src/app/pages/inventory/PackagingMaterialsPage.tsx`, `PackagingMaterialDetailModal.tsx`, `RawDeptMaterialPage.tsx`
- `_System_Core/specs/2026-06-05_packaging-production-deduct.md`
