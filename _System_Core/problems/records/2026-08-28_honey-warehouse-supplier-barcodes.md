---
date: 2026-08-28
type: problem-record
status: fixed
area: honey-warehouse
tags: [problem, honey, warehouse, barcode, production]
---

# Problem: Honey warehouse stock-out required manual row selection

## Symptom

Warehouse staff could see supplier/date codes such as `NF082826`, but the code was not a printable warehouse label identity and stock-out required opening a specific warehouse row. Repeated stock-outs could also create multiple Production rows instead of one daily supplier/variant record.

## Cause

→ [[causes/honey-warehouse-supplier-date-barcode]]

## Fix

→ [[fixes/honey-warehouse-supplier-date-barcode]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase71_honey_warehouse_barcodes.sql` |
| Shared backend | `includes/honey_warehouse_barcodes.php` |
| Honey backend | `includes/honey_inventory.php`, `api/v1/honey.php` |
| Permissions | `includes/permissions.php`, `app/src/app/lib/honeyPermissions.ts` |
| Warehouse API client | `app/src/app/lib/honeyWarehouseBarcodes.ts` |
| Warehouse UI | `app/src/app/pages/honey/HoneyWarehousePage.tsx` |
| Production UI | `app/src/app/pages/honey/HoneyProductionPage.tsx` |

## Deploy

- Run `sql/migration_phase71_honey_warehouse_barcodes.sql` on the target database.
- Deploy PHP and the rebuilt `app/dist/`; hard-refresh Apache/browser clients.
- Regenerate memory-bank indexes after deployment.

## Links

- **Fragile edges:** [[003_Fragile_Edges#Honey module circular dependency]]
- **Patterns:** [[005_SPA_UX_Patterns#Honey / inventory UX]]
- **Plan:** Honey Warehouse Supplier Barcode Flow

## AI rules

Keep supplier/date lookup collision-safe and retain the event ledger when aggregating daily Production rows.
