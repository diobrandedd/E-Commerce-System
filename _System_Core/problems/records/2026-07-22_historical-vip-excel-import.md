---
date: 2026-07-22
type: problem-record
status: fixed
area: fulfillment-historical-import
tags: [problem, online-parcels, vip, import]
---

# Problem: Historical VIP ORDER LIST Excel could not land as shipped Online parcels

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Pre-system VIP J&T ORDER LIST workbooks (Parcel Name + Remarks, no waybill column) had no safe path into `online_order_parcels` as already-shipped history. Live promote always inserts `packing` and requires a unique tracking number; shipping deducts inventory.

## Cause

→ [[causes/no-historical-shipped-import-path]]

## Fix

→ [[fixes/historical-vip-excel-import]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/historical_orders_import.php` |
| API | `api/v1/historical_orders.php` |
| Storage | `includes/rts_storage.php` (`historical_orders` bucket) |
| SPA | `app/src/app/pages/fulfillment/HistoricalOrdersImportPage.tsx` |
| SPA API | `app/src/app/lib/historicalOrdersApi.ts` |
| Nav / routes | `app/src/app/components/Sidebar.tsx`, `app/src/app/routes.ts` |
| Related | Reuses `order_remarks` `vip_order_list` detect |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Regenerate memory bank (verify already ran generate)

## Links

- **Fragile edges:** Online parcels unique TN; inventory deduct on ship only
- **Related:** Partner sold reconcile (stash/preview pattern)
