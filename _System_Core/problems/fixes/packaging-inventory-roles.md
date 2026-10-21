---
type: fix
tags: [pattern, packaging, inventory, roles, hr, prod_manager, executive, barcodes, scan]
---

# Fix: Packaging Materials — exec stock-in / scan stock-out / barcodes

## Operational model (2026-10-06)

| Role | Warehouse packaging (`stocks.on_hand`) | For-packing staging | On-hold parcels |
|------|----------------------------------------|---------------------|-----------------|
| **HR** | No Supplies page | — | — |
| **Assigned** (`packaging.deduct`, not executive) | **Scan stock out** + **Report damage** (no catalog) | **Issue** materials, view counters | Re-waybill + ship (Deliveries) |
| **csr_tl** | No access | No access | **View only** (Online Parcels `on_hold`) |
| **Executive** | Stock-in lot, catalog, set on-hand, pool yield, print barcodes, deduct | All | All |

**Ship deduct:** fulfillment packaging consumes **staging** (`fulfillment_packing_balances.consumed_qty`), not warehouse `on_hand`. PM must **issue** to packing before ship.

**Product create:** honey bottling / dept produce **do not** auto-deduct packaging materials. Scan stock-out is required.

**Level %:** `on_hand / last lot:% stock_entries qty × 100`.

## API / helpers

| Action | Role | Helper |
|--------|------|--------|
| `stock_in_lot` (packaging) | Executive only | `material_inventory_role_can_stock_in_packaging()` |
| `packaging_deduct` / scan | `packaging.deduct` | `material_packaging_deduct_for_use` |
| `packaging_report_damage` | `packaging.deduct` | `material_packaging_report_damage` |
| `packaging_damage_totals` | view | `material_packaging_damage_totals` |
| `packaging_print_sheet` | `packaging.view` | `material_packaging_print_sheet_html` |
| `packaging_lookup_barcode` | view/deduct | `material_packaging_lookup_by_barcode` |
| `packaging_access_roster` | view | `material_packaging_access_roster` |
| `packing_issue_create` | PM, exec | `material_inventory_role_can_issue_packing()` |

## SPA

- Packaging Materials: Level column, Generate all barcodes, Scan stock out, Report damage, damage period totals, access strip
- No Deduct for use on detail modal — scan stock out / report damage only
- Guide updated for scan-first + damage workflow

## Files

- `sql/migration_phase90_packaging_material_barcodes.sql`
- `includes/material_inventory.php`, `honey_inventory.php`, `dept_production.php`, `honey_volume.php`, `permissions.php`
- `api/v1/materials.php`
- `app/src/app/pages/inventory/PackagingMaterialsPage.tsx`
- `app/src/app/components/inventory/PackagingScanStockOutModal.tsx`
- Spec: [[specs/2026-10-06_packaging-barcode-stock-out]]
