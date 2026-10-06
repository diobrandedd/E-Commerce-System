---
date: 2026-10-06
type: problem-record
status: fixed
area: packaging-inventory
tags: [packaging, barcode, scan, stock-out, level-pct]
---

# Problem: Packaging materials needed barcodes, scan stock-out, and stock level %

> Packaging warehouse stock-out was mixed with auto-deduct on product create; no scannable codes or level %.

## Symptom

Packaging Materials lacked barcodes / A4 print-all, scan-to-stock-out, and a clear stock level signal. Bottling/produce could auto-deduct packaging; stock-in was shared with HR/PM.

## Cause

→ [[causes/packaging-no-barcode-scan-stockout]]

## Fix

→ [[fixes/packaging-barcode-scan-stockout]] · [[fixes/packaging-inventory-roles]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase90_packaging_material_barcodes.sql` |
| Includes | `includes/material_inventory.php`, `honey_inventory.php`, `dept_production.php`, `honey_volume.php`, `permissions.php` |
| API | `api/v1/materials.php` |
| SPA | `PackagingMaterialsPage.tsx`, `PackagingScanStockOutModal.tsx`, guide + Settings |
| Spec | [[specs/2026-10-06_packaging-barcode-stock-out]] |

## Related

- [[003_Fragile_Edges]] packaging bullets updated
