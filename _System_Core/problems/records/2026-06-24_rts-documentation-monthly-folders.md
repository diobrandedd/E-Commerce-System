---
date: 2026-06-24
type: problem-record
status: fixed
area: rts-storage
tags: [problem, rts, storage]
---

# Problem: RTS Records blank images/videos (monthly folders + SPA URLs)

## Symptom

RTS Records table showed broken Before ship / Photo / Video thumbnails after RTS documentation moved to `rts_documentation/YYYY-MM/`. All media columns failed in the SPA at `/rts/app/dist/...`.

## Cause

1. **SPA-relative URLs** — `doc_url_base` and `before_shipment.download_url` used `api/storage_media.php?...` without `/rts/` prefix. From basename `/rts/app/dist`, the browser resolved to `.../app/dist/rts/api/...` (404).
2. **Unencoded monthly paths** — SPA `rtsDocUrl()` concatenated `2026-06/file.jpg` raw into `f=` query param.
3. **Partial disk resolve** — `rts_storage_resolve_file()` only globbed monthly folders when DB stored a flat filename, not the reverse (monthly DB path, flat disk).

## Fix

- `rts_storage_public_prefix_for_app()` / `rts_storage_public_url_for_app()` → `/rts/api/storage_media.php?...` for catalog, before-shipment, uploads, deliveries enrichment.
- Bidirectional resolve: try stored path, flat basename, then `glob(bucket/*/basename)`.
- `rtsDocUrl()` encodes path segments per `/`.

## Files

| Role | Path |
|------|------|
| Storage | `includes/rts_storage.php` |
| RTS API | `includes/rts_case_handlers.php`, `includes/rts_attachments.php`, `includes/rts_prefill.php` |
| Deliveries | `includes/online_order_parcels.php`, `api/delivery_match_tracking.php` |
| SPA | `app/src/app/components/rts/RtsCaseMediaUi.tsx` |
