---
status: shipped
spec: "[[specs/2026-10-06_packaging-barcode-stock-out]]"
created: 2026-10-06
approved_by: chat
---

# Implementation Plan: Packaging barcodes + scan stock-out

> Spec: [[specs/2026-10-06_packaging-barcode-stock-out]]

## Summary

Add `stocks.barcode`, last-lot level %, A4 generate-all labels (name under bars), scan stock-out UX, executive-only packaging stock-in, and remove product-create packaging auto-deduct.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]
- Reuse `sku_barcode_render_packed_label_sheet` / Picqer Code128

## Impact

| Area | Change |
|------|--------|
| DB | `migration_phase90_packaging_material_barcodes.sql` |
| Includes | `material_inventory.php`, `honey_inventory.php`, `dept_production.php`, `permissions.php` |
| API | `materials.php` print/lookup/roster |
| SPA | PackagingMaterialsPage + scan modal + guide |

See Cursor plan `packaging_barcode_stock-out_9fd2889e` for phases.
