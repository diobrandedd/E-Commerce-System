---
status: shipped
spec: "[[specs/Inventory Arrangement change]]"
created: 2026-06-05
approved_by: human
approved_date: 2026-06-05
---

# Implementation Plan: Inventory Arrangement change

> **Shipped 2026-06-05.** Spec: [[specs/Inventory Arrangement change]]

## Summary

Four-branch inventory IA (Raw Materials, Packaging, Supplies, Products) in sidebar and routes; `materials.php` API with executive catalog/adjust and prod-manager requests; `migration_phase20_inventory_arrangement.sql`; split dept product routes under Products.

## Delivered

- [x] Phase 1 — `sql/migration_phase20_inventory_arrangement.sql`
- [x] Phase 2 — `includes/material_inventory.php`, `api/v1/materials.php`
- [x] Phase 3 — `routes.ts`, `Sidebar.tsx`, route aliases, `product_key` filter on `dept_list`
- [x] Phase 4 — inventory hub/pages, modals, `InventoryRequestsPage` materials section
- [x] Phase 5 — theory/fragile edges, problem record, memory bank

## Record

[[problems/records/2026-06-05_inventory-arrangement]]
