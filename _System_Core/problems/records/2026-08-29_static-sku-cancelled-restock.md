---
date: 2026-08-29
type: problem-record
status: fixed
area: fulfillment
tags: [cancelled-restock, static-sku, inventory, rollout]
---

# Problem: Static SKU cancelled returns could not be quantity-restocked

## Symptom

Cancelled Restock accepted unique packed/shipped/POS sticker returns, but permanent
SKU barcodes represent many identical units and had no way to capture the return
quantity or destination inventory pool.

## Cause

→ [[causes/static-sku-cancelled-restock]]

## Fix

→ [[fixes/static-sku-cancelled-restock]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase72_sku_cancel_restock.sql` |
| Backend | `includes/pack_cancel_restock.php` |
| API | `api/v1/pack_cancel_restock.php` |
| SPA client | `app/src/app/lib/packCancelRestock.ts` |
| SPA | `app/src/app/pages/deliveries/DeliveriesCancelledPage.tsx` |
| Guide | `app/src/app/components/fulfillment/DeliveriesCancelledGuideModal.tsx` |
| Related | `app/src/app/components/honey/PackagingScanLogModal.tsx` |

## Deploy

- Run phase 72 after phases 61 and 69.
- Verify `pack_cancel_restock_sku_items` exists and `honey_packaging_rows.production_row_id`
  accepts NULL.
- Deploy PHP/API and SPA, then hard-refresh the SPA.
- Perform staging checks for Honey and every department SKU through both supported pools,
  repeated scans, mixed unique/static sessions, discard, and history audit details.

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory and stock pools]]
- **Patterns:** [[005_SPA_UX_Patterns#Feedback animations & hover]]

## AI rules

Do not represent a permanent SKU barcode as a fake unique unit; queue its quantity and
destination pool and credit aggregate inventory only on session confirmation.
