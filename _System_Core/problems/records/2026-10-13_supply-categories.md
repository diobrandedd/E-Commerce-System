---
date: 2026-10-13
type: problem-record
status: fixed
area: packaging-supplies
tags: [supplies, category]
---

# Problem: Supplies could not be grouped by a user-defined category

## Symptom

Add item on Supplies only asked for name and unit. There was no way to create categories or assign one to a supply.

## Cause

`stocks.category` is the system bucket (`packaging`), not a catalog grouping the executive can name.

## Fix

`packaging_supply_categories` plus `stocks.supply_category_id`. Add item and the item detail let an executive create a category and assign it. The catalog shows the category on each row.

## Files

| Role | Path |
|------|------|
| SQL | `sql/migration_phase94_packaging_supply_categories.sql` |
| Include | `includes/material_inventory.php` |
| API | `api/v1/materials.php` (`supply_categories_list`, `supply_category_create`) |
| SPA | `MaterialCatalogModal.tsx`, `PackagingMaterialDetailModal.tsx`, `PackagingMaterialsPage.tsx` |
