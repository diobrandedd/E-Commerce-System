---
date: 2026-06-24
type: problem-record
status: fixed
area: rts-storage
tags: [problem, rts, storage]
---

# Problem: RTS documentation stored flat while deliveries use monthly folders

## Symptom

RTS proof photos/videos lived directly under `rts_documentation/`; delivery photos use `deliveries_documentation/YYYY-MM/` for uniform monthly organization.

## Cause

Upload handlers wrote to bucket root only; storage helpers had month layout for deliveries bucket only.

## Fix

- `rts_storage_dir_rts_month()` + `rts_storage_rts_month_key()` mirror deliveries layout.
- `rts_documentation_save_upload()` stores as `YYYY-MM/rtsdoc_*` / `rtsvid_*`.
- RTS bucket uses `storage_media.php` URLs and monthly path validation like deliveries.
- Legacy flat filenames still resolve via root + `glob(*/file)`.

## Files

| Role | Path |
|------|------|
| Storage | `includes/rts_storage.php` |
| Upload | `includes/rts_attachments.php`, `includes/rts_case_handlers.php`, `api/upload_documentation.php` |
