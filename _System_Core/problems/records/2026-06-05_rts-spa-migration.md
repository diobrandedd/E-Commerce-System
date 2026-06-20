---
date: 2026-06-05
type: problem-record
status: fixed
area: rts
tags: [problem, spa-migration]
---

# Problem: RTS record/records/dashboard stuck on legacy PHP + dual auth

> Main hub — CSR SPA could not reliably record RTS cases because legacy APIs used `app_users` auth while the SPA uses IMS `users`.

## Symptom

`/rts/record` routed to inventory (`RTSPage`). CSR **Record RTS** opened legacy `record.php` / `api/case.php`, which failed or behaved inconsistently for IMS-logged-in users. Inventory sync role mismatch blocked CSR sync after case save.

## Cause

Dual auth trap: SPA (`api/v1/*`, `users`) vs legacy RTS (`rts_auth_user()`, `app_users`). No v1 case CRUD endpoints; ~2k-line PHP surfaces not migrated.

## Fix

Extracted `includes/rts_case_handlers.php`; added `api/v1/rts_cases.php` (case CRUD, list, dashboard, catalog, delivery match, doc upload). Built `RTSRecordPage`, `RTSRecordsPage`, `RTSDashboardPage`; fixed routes and sidebar; legacy PHP redirects to SPA; widened `rts_sync` roles for CSR.

## Files

| Role | Path / note |
|------|-------------|
| Handlers | `includes/rts_case_handlers.php` |
| v1 API | `api/v1/rts_cases.php`, `api/v1/rts_sync.php` |
| Legacy wrapper | `api/case.php` |
| SPA | `app/src/app/pages/rts/*.tsx`, `app/src/app/lib/rtsApi.ts`, `app/src/app/components/rts/RtsLineBuilder.tsx` |
| Redirects | `record.php`, `records.php`, `dashboard.php` |
| Spec | [[specs/2026-06-05_RTS-SPA-Migration]] |
| Plan | [[plans/archive/2026-06-05_rts_spa_migration_plan]] |

## Deploy

- `cd app && npm run build` + hard refresh Apache `app/dist/`
- `php tools/memory_bank/generate_all.php`

## Links

- **Fragile edges:** [[003_Fragile_Edges#Dual auth systems]]
- **Spec:** [[specs/2026-06-05_RTS-SPA-Migration]]

## AI rules

New RTS case writes from SPA → `api/v1/rts_cases.php` only. Do not wire SPA forms to `api/case.php`.
