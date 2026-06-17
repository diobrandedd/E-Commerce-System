---
type: fix
tags: [fix, packaging, inventory, roles, hr, prod_manager, executive, staging]
---

# Fix: Packaging Materials — HR price / PM stock-in / packing staging

## Operational model (2026-06-17)

| Role | Warehouse packaging (`stocks.on_hand`) | For-packing staging | On-hold parcels |
|------|----------------------------------------|---------------------|-----------------|
| **HR** | **Update price from invoice** only — no qty stock-in | — | — |
| **prod_manager** | **Stock in lot** + **deduct for use** | **Issue** materials, view counters | Re-waybill + ship (Deliveries) |
| **csr_tl** | No access | No access | **View only** (Online Parcels `on_hold`) |
| **Executive** | All + set on-hand, pool yield, catalog | All | All |

**Ship deduct:** fulfillment packaging consumes **staging** (`fulfillment_packing_balances.consumed_qty`), not warehouse `on_hand`. PM must **issue** to packing before ship.

## API / helpers

| Action | Role | Helper |
|--------|------|--------|
| `stock_in_lot` (packaging) | PM, exec | `material_inventory_role_can_stock_in_packaging()` |
| `packaging_price_lot` | HR, exec | `material_inventory_role_can_edit_packaging_price()` |
| `packing_issue_create` | PM, exec | `material_inventory_role_can_issue_packing()` |
| `hold_parcel` / `rewaybill` | PM, exec | Deliveries |

## SPA

- **For packing:** `/fulfillment/packing-materials` — `PackingMaterialsStagingPage.tsx`
- **HR:** `PackagingPriceLotModal` on packaging detail (not stock-in)
- **PM:** stock-in + staging issue; Deliveries tabs Ship / On hold / Re-waybill
- **CSR TL:** Online Parcels filter `on_hold` (read-only)

## Files

- `includes/fulfillment_packing.php`
- `includes/material_inventory.php` — `parcel_fulfillment_apply_deduct` → staging
- `api/v1/fulfillment_packing.php`, `api/v1/materials.php`, `api/v1/deliveries.php`
- `sql/migration_phase34_fulfillment_packing_staging.sql`

Supersedes HR stock-in row in prior version of this doc.
