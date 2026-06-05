---
date: 2026-06-04
type: problem-record
status: fixed
area: fulfillment
tags: [problem, inventory, enhancement]
---

# Problem: Online orders not connected to deliveries and ship deduct

## Symptom

No closed loop from CSR batch finalize → packing parcels → PM deliveries scan/ship → inventory deduct.

## Cause

CSR batches and production shipping were separate systems without parcel promotion or ship deduct.

## Fix

`batch_finalize` promotes to `online_order_parcels`; Deliveries SPA for exec/PM; `ship_parcel` FIFO packaging deduct; parcel lookup for CSR.

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase18_online_order_parcels.sql` |
| Parser | `includes/honey_parcel_item_parser.php` |
| Inventory | `includes/honey_parcel_inventory.php` |
| Parcels | `includes/online_order_parcels.php` |
| Deliveries | `includes/deliveries_upload.php` |

## Links

- **Plan:** [[plans/archive/2026-06-04_Inventory-Connection-to-Everything_plan]]
- **Spec:** [[specs/Inventory Connection to Everything]]
