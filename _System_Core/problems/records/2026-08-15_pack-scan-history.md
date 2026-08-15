---
date: 2026-08-15
type: problem-record
status: fixed
area: pack-scan-history
tags: [problem, honey, pack-scan, report]
---

# Problem: No UI to review who packed which bottles / batches

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Pack scan wrote confirmed sessions to the DB (who, when, bottles) but the SPA only offered the live
scanner. Ops could not answer “who packed how many of batch X on day Y” without SQL.

## Cause

→ [[causes/pack-scan-history-missing-ui]]

`honey_bottle_pack_sessions` / items existed since phase54; only start/scan/confirm/discard APIs
were exposed. No list endpoint or History UI.

## Fix

→ [[fixes/pack-scan-history-tab]]

- Read-only `pack_session_list` (from/to dates, optional `batch_no` prefix, pagination).
- Enriched session get: confirmer name, `batch_no` per bottle, `by_batch` rollup.
- Pack scan page: **Scan | History** tabs with filter bar and expandable session detail.
- No migration; no UPDATE/DELETE on existing live pack rows.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` — `pack_session_list` |
| SPA | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |
| SPA | `app/src/app/lib/bottleBarcodes.ts` |
| Spec | [[specs/2026-08-15_pack-scan-history]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh. No SQL.

## AI rules (optional)

History APIs must stay read-only SELECTs. Do not add delete/edit of confirmed pack sessions without a separate approval workflow.
