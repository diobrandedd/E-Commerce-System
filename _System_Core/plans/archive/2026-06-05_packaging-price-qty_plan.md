---
status: shipped
spec: "[[specs/Inventory Arrangement change]]"
created: 2026-06-05
approved_by: human
approved_date: 2026-06-05
---

# Implementation Plan: Packaging materials — price, quantity, costing link

> **Shipped 2026-06-05.** Builds on [[plans/archive/2026-06-05_inventory-arrangement_plan]].

## Summary

Executive and HR set absolute unit price and on-hand on Packaging Materials; `set_values` API triggers costing recalc; formula picker groups packaging stocks.

## Delivered

- [x] `material_packaging_set_values` + `materials.php set_values`
- [x] `PackagingMaterialsPage` + `PackagingSetValuesModal`
- [x] Costing `stocks_list` + packaging optgroup
- [x] Supplies delta/request unchanged

## Record

[[problems/records/2026-06-05_packaging-price-qty]]
