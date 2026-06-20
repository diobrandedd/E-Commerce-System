---
date: 2026-06-05
type: problem-record
status: fixed
area: dept-brc-costing
tags: [problem, dept, brc, costing]
---

# Problem: BRC used 1:1 placeholder — no 20 kg bag → 100 g pouch BOM

> Phase 16 seeded `Black Rice Coffee — unit` at qty 1.0. Real flow: supplier ₱/bag (20 kg), sell pouch ~100 g → 200 pouches/bag.

## Fix

- `sql/migration_phase18_brc_supplier_bom.sql` — `Black Rice Coffee — raw` + `BRC pouch packaging`, formula `0.005` + `1`
- Extended `dept_products.php`, `dept_pricing.php` (`brc` → `black_rice` suppliers), generic `dept_product_pouches_per_bag()`
- `SupplierDeptPricesInline` + `SuppliersPage` black_rice; `DeptInventoryPage` cacao_brc stock-in

## Business math

→ [[specs/2026-06-05_brc-bom-math]]

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase18_brc_supplier_bom.sql` |
| Catalog | `includes/dept_products.php`, `app/src/app/lib/deptProducts.ts` |
| Suppliers UI | `SupplierDeptPricesInline.tsx`, `SuppliersPage.tsx` |
| Inventory | `DeptInventoryPage.tsx`, `includes/dept_inventory.php` |

## Deploy

- Run `migration_phase18_brc_supplier_bom.sql` after phase 17
- `cd app && npm run build`
