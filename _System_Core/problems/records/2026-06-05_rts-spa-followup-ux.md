---
date: 2026-06-05
type: problem-record
status: fixed
area: rts
tags: [problem, spa-ux]
---

# Problem: RTS SPA follow-up — sidebar, sync feedback, records table UX

> Follow-up polish after [[records/2026-06-05_rts-spa-migration]].

## Symptom

- RTS sidebar had separate top-level items (inventory, record, records, dashboard).
- **Sync now** on inventory appeared to do nothing; errors were swallowed; empty `rts_line_items` was unclear.
- **RTS Records** filter card was oversized; table lacked system-standard pagination footer and line-count visibility.

## Cause

- Nav not grouped like Deliveries/Inventory.
- `RTSPage` called sync with `.catch(() => null)` and no toast; HR blocked from `rts_sync` while page allowed click.
- Records page used a large 3-row filter grid; pagination hidden when `totalPages === 1`; list API omitted `line_count`.

## Fix

- **Sidebar:** single **RTS** parent with sub-items (RTS inventory, RTS Records, RTS Dashboard, Record RTS); auto-expand on `/rts/*`.
- **RTSPage:** dedicated `syncNow()` with spinner + toast; surface sync errors; allow HR sync; clearer empty-lines banner with CTAs.
- **RTSRecordsPage:** compact collapsible filters; status badges; **Lines** column (`line_count` from API); `TablePagination` footer (Online Parcels pattern) — always visible when rows exist, 10/page server-side.
- **Backend:** `rts_case_handler_list` returns `line_count` subquery; `rts_sync` allows `hr`.

## Files

| Role | Path |
|------|------|
| Sidebar | `app/src/app/components/Sidebar.tsx` |
| Inventory | `app/src/app/pages/RTSPage.tsx` |
| Records | `app/src/app/pages/rts/RTSRecordsPage.tsx` |
| API types | `app/src/app/lib/rtsApi.ts` |
| List handler | `includes/rts_case_handlers.php` |
| Sync roles | `api/v1/rts_sync.php` |

## Deploy

- `cd app && npm run build` + hard refresh
- `php tools/memory_bank/generate_all.php`

## Links

- **Parent:** [[records/2026-06-05_rts-spa-migration]]
- **Spec:** [[specs/2026-06-05_RTS-SPA-Migration]]
- **Patterns:** [[005_SPA_UX_Patterns]] · [[004_SPA_Design_System]]

## AI rules

RTS list pagination: server-side `page`/`per_page` + footer `TablePagination` (match Online Parcels). Show **Lines** column — inventory sync needs `rts_line_items`.
