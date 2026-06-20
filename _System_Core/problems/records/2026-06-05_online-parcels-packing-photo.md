---
date: 2026-06-05
type: problem-record
status: fixed
area: online-parcels
tags: [problem, parcels, enhancement]
---

# Problem: Online parcel detail had no packing photo

## Symptom

Could not see Deliveries scan photo from Online Parcels modal.

## Cause

→ [[causes/packing-photo-not-on-parcel-api]]

## Fix

→ [[fixes/enrich-parcel-api-with-packing-photo]]

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/online_order_parcels.php`, `includes/rts_storage.php` |
| API | `api/v1/parcels.php` |
| SPA | `OnlineParcelDetailModal.tsx`, `parcelsApi.ts` |

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Online parcel detail (packing photo)]]
