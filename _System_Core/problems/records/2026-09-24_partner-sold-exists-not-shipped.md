---
date: 2026-09-24
type: problem-record
status: fixed
area: fulfillment-online-parcels
tags: [problem, feature]
---

# Problem: Partner sold list showed packing parcels as missing

## Symptom

Aug partner sold-list Excel reconciled to 0 matches even though every Order No was visible in Online Parcels (status packing). Export also inflated thousands of blank side-sheet rows.

## Cause

→ Tool matched only `status = shipped`, and labeled misses “Not in system.” Scanner inherited the last Order No into ~6k empty rows under the sheet max row.

## Fix

- Match **existence** in Online Parcels for the partner (any status), via Order No (float-tolerant) and Tracking Number.
- Green / red only; append **Exists** / **Not found** column on export and preview.
- Stop blank-row inheritance; only continue Order No on real multi-line item rows.

## Files

| Role | Path / note |
|------|-------------|
| PHP | `includes/partner_sold_reconcile.php` |
| Python | `includes/partner_sold_workbook.py` |
| SPA | `app/src/app/pages/fulfillment/PartnerSoldReconcilePage.tsx` |
| API client | `app/src/app/lib/partnerSoldReconcileApi.ts` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- Related: [[problems/records/2026-07-01_partner-sold-list-reconcile]]
