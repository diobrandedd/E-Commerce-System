---
date: 2026-10-13
type: problem-record
status: fixed
area: packaging-supplies
tags: [supplies, category, note]
---

# Problem: Supply categories were buried in Add item, and rows had no note

## Symptom

Categories could only be created inside Add item or one supply at a time. Catalog rows had no place for where an item came from or other details.

## Cause

Category create lived inside the add and detail forms. `stocks` had no free-text field for a supply note.

## Fix

**Categories** on the supplies header (and mobile Actions) creates a category and assigns many supplies at once. Each supply stores `supply_note` (up to 500 characters), shown under the name and editable on the item for executives.

## Files

| Role | Path |
|------|------|
| SQL | `sql/migration_phase95_packaging_supply_note.sql` |
| Include | `includes/material_inventory.php` |
| API | `api/v1/materials.php` (`supply_category_assign`, `supply_note` on create/update) |
| SPA | `SupplyCategoriesModal.tsx`, `PackagingMaterialsPage.tsx`, `MaterialCatalogModal.tsx`, `PackagingMaterialDetailModal.tsx` |

## Deploy

- Run phase 95 on each database, then `cd app && npm run build` and hard-refresh.
