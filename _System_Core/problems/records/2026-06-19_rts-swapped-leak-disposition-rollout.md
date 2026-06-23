---
date: 2026-06-19
type: problem-record
status: fixed
area: rts
tags: [problem, spa, ux]
---

# Problem: RTS swapped/leaks/records — text-only media links, empty registries

> Hub for RTS disposition rollout traps (migration, roles, registry UX).

## Symptom

- Swapped / Leaks tabs empty after save although case existed in RTS Records.
- Re-entering same TN on Record RTS showed previous photo/video (append mode).
- Swapped/leaks tables showed "Download" / "image" / "video" text — no thumbnails; rows not tappable for detail modal.

## Causes

1. **`migration_phase44` not run** — `case_disposition` column missing; list APIs returned `[]`; inserts still set `damaged_reason`.
2. **CSR role mismatch** — IMS `tl`/`staff` in CSR dept blocked upload/save (`403 Forbidden`) while SPA allowed `/rts/record`.
3. **Record flow** — existing TN opened append modal with old attachments loaded.
4. **Registry UI** — no shared media thumb + detail modal pattern.

## Fixes

| Area | Fix |
|------|-----|
| DB | Run `sql/migration_phase44_rts_case_disposition.sql`; backfill from `damaged_reason` |
| API | `api_rts_cases_user_effective_role()` for write gates |
| API | `rts_case_disposition_registry_where()` — legacy `damaged_reason` fallback when column missing |
| API | `before_shipment` on `cases_list` + `case_get` |
| SPA | `RtsCaseMediaUi.tsx` — thumbs, gallery, registry detail modal |
| SPA | Swapped / Leaks / Records — tappable rows, Before ship / Photo / Video columns |
| SPA | Record RTS — block duplicate TN for swapped/leak; append hides docs |

## Files

- `app/src/app/components/rts/RtsCaseMediaUi.tsx`
- `app/src/app/pages/rts/RTSSwappedPage.tsx`, `RTSLeaksPage.tsx`, `RTSRecordsPage.tsx`
- `includes/rts_case_handlers.php`, `api/v1/rts_cases.php`
- `sql/migration_phase44_rts_case_disposition.sql`

## Deploy checklist

1. Run migration_phase44 on prod **once**
2. Deploy PHP + `app/dist/`
3. Hard refresh mobile (ngrok)

## Links

- Spec: [[specs/2026-06-19_rts-swapped-leak-disposition]]
- Fragile: [[003_Fragile_Edges#IMS role vs SPA role (API gates)]]
