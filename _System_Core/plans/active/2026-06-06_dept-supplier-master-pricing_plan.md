---
status: draft
approved: false
created: 2026-06-06
spec: ../specs/2026-06-05_dept-products-pt-brc-cacao.md
continues: ../problems/records/2026-06-05_dept-products-pt-brc-cacao.md
---

# Plan (draft): Dept products — Supplier Master pricing (v2)

> Resume tomorrow. Align PT/BRC/Jar Cacao with honey pricing workflow.

## Goal

Executive sets **₱/unit** per dept supplier in Supplier Master; stock-in picks supplier + qty + date (no manual unit cost).

## Tasks

1. Migration: `supplier_dept_prices` (`supplier_id`, `product_key`, `price_per_unit`, `effective_from`)
2. `includes/dept_pricing.php` — list/save prices, upsert costing `stocks` on save (mirror `honey_pricing.php`)
3. SPA: `SupplierDeptPricesInline` on Suppliers page for `paragis` / `black_rice` / `cacao` suppliers
4. Change `dept_inventory_stock_in()` — `supplier_id` required; resolve price from list
5. `DeptInventoryPage` — supplier combobox; remove unit cost field
6. E2E + memory bank update

## Approval

- [ ] Human approved before Agent implementation
