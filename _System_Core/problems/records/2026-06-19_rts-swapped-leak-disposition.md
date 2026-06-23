# Problem: RTS swapped/leak disposition + tracking prefill

## Problem

RTS treated all damaged returns as one bucket; all line items synced to sellable pool. CSR retyped parcel data already in Online Orders.

## Cause

Single `damaged_reason` enum; no disposition filter on inventory sync; no prefill API.

## Fix

- `migration_phase44_rts_case_disposition.sql` — `case_disposition`, `before_shipment_photo_filename`
- `includes/rts_prefill.php` — tracking prefill + items_raw → RTS lines
- `includes/rts_case_handlers.php` — validation, list_swapped/list_leaks, non-blocking before-shipment link
- `includes/rts_inventory_sync.php` — exclude `swapped` from sync
- SPA: `RTSRecordPage` wizard, `RTSSwappedPage`, `RTSLeaksPage`, sidebar routes

## Files

- `sql/migration_phase44_rts_case_disposition.sql`
- `includes/rts_prefill.php`
- `includes/rts_case_handlers.php`
- `includes/rts_inventory_sync.php`
- `api/v1/rts_cases.php`
- `app/src/app/pages/rts/RTSRecordPage.tsx`
- `app/src/app/pages/rts/RTSSwappedPage.tsx`
- `app/src/app/pages/rts/RTSLeaksPage.tsx`
- `app/src/app/lib/rtsApi.ts`
- `app/src/app/routes.ts`
- `app/src/app/components/Sidebar.tsx`
