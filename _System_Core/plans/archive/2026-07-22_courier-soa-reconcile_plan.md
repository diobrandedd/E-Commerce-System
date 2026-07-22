---
status: shipped
spec: "[[specs/2026-07-22_courier-soa-reconcile]]"
created: 2026-07-22
approved_by: chat
---

# Implementation Plan: Courier SOA reconcile

> Spec: [[specs/2026-07-22_courier-soa-reconcile]]

## Summary

Mirror Partner sold reconcile: stash SOA Excel → Python scan (J&T Waybill / SPX Tracking) → load shipped parcels by ship_date + courier bucket → bidirectional set diff → Python highlight green/red on tracking cells → SPA page with both mismatch lists (no amounts).

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]]
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | `api/v1/courier_soa_reconcile.php` stash/reconcile/download |
| Includes | `courier_soa_reconcile.php`, `courier_soa_workbook.py`; `rts_courier_label_bucket` in `courier_detect.php`; storage bucket |
| DB | none |
| SPA | page, API client, route, sidebar |
| Legacy / Python | openpyxl sidecar |

## Implementation steps

### Phase 1 — Backend

1. Storage bucket + domain PHP + Python scan/highlight
2. API endpoint

### Phase 2 — Frontend

1. Client, page, Guide, route, nav

### Phase 3 — Cleanup

1. Verify, memory bank, archive plan

## Files to touch (expected)

| File | Action |
|------|--------|
| `includes/courier_soa_reconcile.php` | create |
| `includes/courier_soa_workbook.py` | create |
| `api/v1/courier_soa_reconcile.php` | create |
| `includes/courier_detect.php` | add label bucket helper |
| `includes/rts_storage.php` | bucket constant |
| `app/src/app/lib/courierSoaReconcileApi.ts` | create |
| `app/src/app/pages/fulfillment/CourierSoaReconcilePage.tsx` | create |
| `app/src/app/components/fulfillment/CourierSoaGuideModal.tsx` | create |
| `app/src/app/routes.ts` | route |
| `app/src/app/components/Sidebar.tsx` | nav |

## Test plan

- [ ] Upload sample J&T SOA; date range covering known ships; green/red download
- [ ] Upload sample SPX SOA; same
- [ ] Reject unknown Excel headers
- [ ] Missing-from-SOA list shows our TNs not in file

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-07-22
