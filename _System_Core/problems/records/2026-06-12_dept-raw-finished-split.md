---
date: 2026-06-12
type: problem-record
status: fixed
area: inventory
tags: [problem, dept-products, raw-materials]
---

# Problem: Raw materials and finished products shared one stock-in flow

> Paragis / BRC / Jar Cacao stock-in on the Products page credited finished units immediately; Raw Materials pages were read-only summaries.

## Symptom

Prod managers could not operate raw bag inventory separately from finished counts. Raw Materials (Dried Paragis, Black Rice, Cacao Beans) looked like costing logs; Products pages mixed supplier stock-in with finished inventory.

## Cause

`dept_inventory_stock_in_supplier_priced()` wrote `stock_entries` and updated `dept_inventory_rows` in one step. `RawMaterialDetailPage` was display-only.

## Fix

→ [[fixes/dept-raw-vs-finished-inventory]]

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/dept_inventory.php`, `api/v1/honey.php` |
| SPA | `RawDeptMaterialPage.tsx`, `DeptProductFinishedPage.tsx`, `deptRawInventory.ts`, `inventoryRoutes.ts` |
| Hub sum | `includes/material_inventory.php` (`material_raw_snapshot`) |

## Deploy

- `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Dept finished product pages]]
