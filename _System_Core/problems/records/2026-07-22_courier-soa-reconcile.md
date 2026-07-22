---
date: 2026-07-22
type: problem-record
status: fixed
area: fulfillment-courier-soa
tags: [problem]
---

# Problem: No courier SOA vs shipped tracking reconcile

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Ops could download J&T / SPX SOA Excel but had no in-app way to confirm which tracking numbers matched shipped Online Parcels for a ship-date range (both directions).

## Cause

→ [[causes/no-courier-soa-reconcile-path]]

## Fix

→ [[fixes/courier-soa-excel-reconcile]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/courier_soa_reconcile.php` |
| Excel | `includes/courier_soa_workbook.py` |
| API | `api/v1/courier_soa_reconcile.php` |
| SPA | `app/src/app/pages/fulfillment/CourierSoaReconcilePage.tsx` |
| Related | [[generated/modules/courier_soa_reconcile]] |
| Spec | [[specs/2026-07-22_courier-soa-reconcile]] |

## Deploy

- `cd app && npm run build` + hard refresh Apache `app/dist/`
- Regenerate memory bank (`php tools/memory_bank/generate_all.php`)

## Links

- Spec: [[specs/2026-07-22_courier-soa-reconcile]]
- Plan archive: [[plans/archive/2026-07-22_courier-soa-reconcile_plan]]
- Sibling: [[problems/records/2026-07-01_partner-sold-list-reconcile]]
