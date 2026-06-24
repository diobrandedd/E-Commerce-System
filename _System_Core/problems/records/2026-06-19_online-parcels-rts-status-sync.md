---
date: 2026-06-19
type: problem-record
status: fixed
area: fulfillment-online-parcels
tags: [problem, rts, online-parcels]
---

# Problem: Online Parcels did not show RTS when CSR recorded return scan

## Symptom

CSR users recording an RTS case on Record RTS had no way to filter matching online parcels as returned — `online_order_parcels` status stayed `shipped`/`packing` even when an active `rts_cases` row existed for the same tracking.

## Cause

RTS create (`rts_case_handler_create`) inserted case + lines only; prefill read parcels but never wrote back status.

## Fix

- Migration phase 49: `status` ENUM + `rts`, link columns `rts_case_id`, `status_before_rts`, `rts_recorded_at`.
- `online_parcels_mark_rts()` on RTS create; `online_parcels_revert_rts_by_case()` on void (restore prior status).
- Online Parcels SPA: RTS filter, badge, detail fields.

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase49_online_parcels_rts_status.sql`, `tools/apply_migration_phase49.php` |
| Backend | `includes/online_order_parcels.php`, `includes/rts_case_handlers.php` |
| SPA | `app/src/app/lib/parcelsApi.ts`, `OnlineParcelsPage.tsx`, `OnlineParcelDetailModal.tsx` |
