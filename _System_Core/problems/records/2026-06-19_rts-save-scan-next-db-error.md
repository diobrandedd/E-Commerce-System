# RTS Save & scan next — DB error then duplicate tracking

## Problem

CSR clicks **Save & scan next** on Record RTS. First attempt shows “Database error while saving.” Retry on the same tracking shows “This tracking number already exists.” Scanner did not auto-open for the next parcel.

## Cause

1. `rts_case_handler_create()` ran `online_parcels_ensure_schema()` and `rts_line_items_ensure_v2_schema()` **inside** an open PDO transaction. MySQL DDL (`CREATE TABLE`) implicitly commits, so the RTS case row could persist while a later step failed and `rollBack()` could not undo it.
2. SPA `save(andScanNext)` reset the form but never called `setScannerOpen(true)`.
3. New scanner modal required manual **Start camera** and often opened the front camera first.

## Fix

- `rts_case_handler_ensure_save_schemas()` runs line-item and parcel schema ensures **before** `beginTransaction()` on create/update/append.
- `finishScanNext()` closes case modal, resets fields, opens scanner; duplicate-tracking on scan-next is treated as already-saved recovery.
- `TrackingQrScannerModal`: `autoStart` (default true) requests `facingMode: environment` on open.

## Files

- `includes/rts_case_handlers.php`
- `app/src/app/pages/rts/RTSRecordPage.tsx`
- `app/src/app/components/scan/TrackingQrScannerModal.tsx`
- `app/src/app/lib/html5QrcodeLoader.ts`
