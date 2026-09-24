---
date: 2026-09-24
type: problem-record
status: fixed
area: fulfillment-online-parcels
tags: [problem, feature]
---

# Problem: Partner sold list checked their rows in ours; needed ours in theirs

## Symptom

Ops needed to know whether **our** Online Parcels appear on the partner’s sold-list Excel. The tool only checked their Excel rows against our DB.

## Cause

Reconcile direction was one-way (their list → our parcels).

## Fix

Direction toggle: **Our list in theirs** (default) loads our parcels and checks Order No / TN against their Excel; exports our list with Exists column. **Their list in ours** keeps the previous highlight flow.

## Files

| Role | Path / note |
|------|-------------|
| PHP | `includes/partner_sold_reconcile.php` |
| Python | `includes/partner_sold_workbook.py` (`export_ours`) |
| SPA | `app/src/app/pages/fulfillment/PartnerSoldReconcilePage.tsx` |
| API client | `app/src/app/lib/partnerSoldReconcileApi.ts` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- Related: [[problems/records/2026-09-24_partner-sold-exists-not-shipped]] · [[problems/records/2026-07-01_partner-sold-list-reconcile]]
