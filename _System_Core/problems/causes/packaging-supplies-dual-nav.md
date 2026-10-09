---
type: cause
tags: [cause]
---

# Cause: Separate Packaging Materials and Supplies SPA pages

## What went wrong

Two inventory routes (`/inventory/packaging-materials` and `/inventory/supplies`) used different catalogs (`packaging` vs `supply`). Packaging Add item also required picking a department even though the warehouse catalog is org-wide in practice.

## Where in code

- `Sidebar.tsx` dual nav children
- `MaterialCatalogModal` department select for non-supply
- `material_inventory_create()` packaging branch required `dept_id`

## Related problems

- [[records/2026-10-09_supplies-replaces-packaging-page]]
