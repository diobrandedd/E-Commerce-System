---
date: 2026-09-15
type: problem-record
status: fixed
area: dept-raw-inventory
tags: [problem]
---

# Problem: Raw stock-in asked for bags instead of total kilos

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Ops stocked Paragis / BRC / Jar Cacao raw by counting sealed bags. Real receiving is **total kilos** (e.g. 12.5). Labels said bags / ₱/bag even though the leaf pool already treated 1 stock-entry unit ≈ 1 kg.

## Cause

→ [[causes/raw-stock-in-bag-unit-label]]

## Fix

→ [[fixes/raw-stock-in-kilos]]

## Files

| Role | Path / note |
|------|-------------|
| Catalog | `includes/dept_products.php` (`stock_in_unit` → `kg` for pt/brc/jar_cacao) |
| Pricing errors | `includes/dept_inventory.php`, `includes/dept_pricing.php` |
| SPA raw page | `app/src/app/pages/inventory/RawDeptMaterialPage.tsx` |
| Pool strip | `app/src/app/components/dept/DeptBagPoolStrip.tsx` |
| Stock-in modal | `app/src/app/components/dept/DeptStockInModal.tsx` |
| Yield / labels | `app/src/app/lib/deptProducts.ts` |
| Supplier prices | `app/src/app/components/suppliers/SupplierDeptPricesInline.tsx` |
| Guides / tours | Paragis + Dept raw guides, Settings BOM copy, Costing/Suppliers guides |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges]] (PT / BRC / Jar Cacao raw stock-in kilos)
- Honey Comb unchanged (still bags)
