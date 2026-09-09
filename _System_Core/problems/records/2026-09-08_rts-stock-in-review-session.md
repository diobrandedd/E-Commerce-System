---
date: 2026-09-08
type: problem-record
status: fixed
area: rts
tags: [rts, barcode, inventory, session]
---

# Problem: RTS stock-in had no review session

## Symptom

The RTS scanner was continuous, but every accepted scan changed sellable inventory immediately. Users could not double-check quantities, remove mistakes, cancel the batch, or confirm the complete stock-in as one operation.

## Cause

→ [[causes/rts-stock-in-immediate-mutation]]

## Fix

→ [[fixes/rts-stock-in-review-session]]

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/rts_stock_in_sessions.php` |
| API | `api/v1/rts.php` |
| Database | `sql/migration_phase76_rts_stock_in_sessions.sql` |
| SPA | `app/src/app/pages/RTSPage.tsx` |
| Scanner | `app/src/app/components/scan/BarcodeCameraScannerModal.tsx` |
| Client | `app/src/app/lib/rtsApi.ts` |
| Regression | `tools/test_rts_stock_in_session.php` |

## Deploy

- Run phase 76.
- Build the SPA and hard refresh deployed clients.
- Regenerate memory-bank maps.

## Links

- **Plan:** [[plans/archive/2026-09-08_rts-stock-in-review-session_plan]]
- **Spec:** [[specs/2026-08-26_sku-barcode-inventory]]

## AI rule

Do not restore immediate RTS stock-in writes or replace presentation-edge detection with raw per-frame acceptance.
