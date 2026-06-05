---
date: 2026-06-05
type: problem-record
status: fixed
area: online-parcels
tags: [problem, auth, api]
---

# Problem: Online Parcels API Forbidden for CSR team leader

## Symptom

CSR TL page loaded but API returned Forbidden — empty table + red banner.

## Cause

→ [[causes/api-gate-checked-spa-role-not-ims]]

## Fix

→ [[fixes/csr-dept-check-on-parcel-api-gate]]

## Files

| Role | Path / note |
|------|-------------|
| Gate | `includes/online_order_parcels.php` |
| API | `api/v1/parcels.php` |
| Module | [[generated/modules/online_order_parcels]] |

## Deploy

- PHP only

## Links

- **Fragile edges:** [[003_Fragile_Edges#IMS role vs SPA role (API gates)]]
