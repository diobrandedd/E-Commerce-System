---
date: 2026-06-05
type: problem-record
status: fixed
area: dept-paragis-costing
tags: [problem, dept, paragis, costing]
---

# Problem: Paragis Tea costing used 1:1 placeholder — no bag→pouch BOM or Supplier Master

> PT v1 seeded `Paragis Tea — unit` with formula qty 1.0; stock-in used manual unit cost. Real flow: supplier ₱/bag (5 kg), 2 g/tea bag, 24 bags/pouch → ~104.17 pouches/bag.

## Symptom

Executive could not set Paragis leaf price in Supplier Master; product baseline did not reflect leaf fraction + packaging; stock-in did not convert bags to sellable pouches.

## Cause

Phase 16 dept rollout used a single-stage placeholder stock/formula for all three dept SKUs. No `supplier_dept_prices`, no leaf/packaging split, no conversion constants.

## Fix

- `sql/migration_phase17_pt_supplier_bom.sql` — `supplier_dept_prices`, `PT pouch packaging` + `Paragis leaf — raw` stocks, 2-line formula (`0.0096` bag/pouch + `1` pouch packaging)
- `includes/dept_pricing.php` — mirror honey: ₱/bag → per-supplier leaf stock upsert
- `includes/dept_inventory.php` — PT stock-in: supplier + bags in → pouches on `dept_inventory_rows`; BRC/Cacao keep manual cost
- SPA: `SupplierDeptPricesInline` (paragis), `DeptInventoryPage` supplier/bag/pouch preview
- API: `supplier_dept_prices`, `supplier_dept_prices_save`, updated `dept_stock_in`

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase17_pt_supplier_bom.sql` |
| Catalog | `includes/dept_products.php`, `app/src/app/lib/deptProducts.ts` |
| Pricing | `includes/dept_pricing.php`, `app/src/app/lib/deptPricing.ts` |
| Inventory | `includes/dept_inventory.php`, `app/src/app/pages/dept/DeptInventoryPage.tsx` |
| Suppliers UI | `app/src/app/components/suppliers/SupplierDeptPricesInline.tsx`, `SuppliersPage.tsx` |
| API | `api/v1/honey.php` |

## Deploy

- Run `migration_phase17_pt_supplier_bom.sql` (after phase 16)
- `cd app && npm run build` + hard refresh
- `php tools/memory_bank/generate_all.php`

## Business math

Full chain (bag → tea bags → pouches; 2500÷24 vs 24÷2500): **[[specs/2026-06-05_paragis-tea-bom-math]]**

## Links

- **Math reference:** [[specs/2026-06-05_paragis-tea-bom-math]]
- **Fragile edges:** [[003_Fragile_Edges#Dept catalog products (PT / BRC / Cacao)]]
- **Spec:** [[specs/2026-06-05_dept-products-pt-brc-cacao]]

## AI rules

PT inventory unit is **pouch**; stock-in qty is **bags** for `pt` only. Packaging price is manual on Costing → Stocks (`PT pouch packaging`). BRC/Cacao still 1:1 placeholder until their BOMs are defined.
