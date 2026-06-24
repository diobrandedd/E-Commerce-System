---
date: 2026-06-19
type: problem-record
status: fixed
area: rts
tags: [problem, spa, mobile]
---

# Problem: RTS Record — no preview after Take photo / Record video

## Symptom

On mobile (ngrok), staff captured photo and video on Record RTS case modal but documentation section stayed empty; swapped-parcel warning still showed photo+video required.

## Cause

Documentation UI only listed attachments after successful server upload. iPhone HEIC photos were rejected server-side (`unsupported_type`). Upload errors appeared only in modal top banner (easy to miss). No local blob preview while uploading.

## Fix

- `RTSRecordPage.tsx`: immediate blob preview, per-item upload state (`uploading` / `ready` / `error`), thumbnail grid, separate `docUploadBusy`, compress images via `compressDeliveryPhoto`, block save while uploads in flight.
- `rts_case_handlers.php`: accept `image/heic` / `image/heif` as fallback.

## Follow-up (2026-06-19): Upload shows "Forbidden"

CSR users are stored in IMS as `tl`/`staff` but SPA normalizes to `csr_tl`/`csr_staff`. `api_rts_cases_role_can_record` checked raw session role → 403 on upload/save. Fixed via `api_rts_cases_user_effective_role()` in `api/v1/rts_cases.php`.

## Follow-up (2026-06-19): Swapped/Leaks tabs empty

`migration_phase44` not applied — `case_disposition` column missing; saves used `damaged_reason` only; registry APIs returned `[]`. Fixed: legacy `damaged_reason` fallback in list queries; migration applied locally; duplicate TN no longer reloads old media for swapped/leak.

## Follow-up (2026-06-19): Multi-attach + long video uploads

Unified camera + gallery into one attachment grid (up to 24 files); parallel uploads; remove per-thumb. Raised app video cap to 512MB (`RTS_DOC_VIDEO_MAX_BYTES`); `api/.htaccess` sets PHP `upload_max_filesize`/`post_max_size` for API routes. Clearer upload error shows current PHP limits.

## Files

| Role | Path |
|------|------|
| Primary | `app/src/app/pages/rts/RTSRecordPage.tsx` |
| Backend | `includes/rts_case_handlers.php`, `includes/rts_attachments.php`, `api/.htaccess` |
