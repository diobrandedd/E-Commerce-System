---
status: shipped
spec: "[[specs/2026-08-26_sku-barcode-inventory]]"
created: 2026-09-08
approved_by: user-chat
completed: 2026-09-08
---

# Implementation Plan: RTS Stock-In Review Session

> Spec: [[specs/2026-08-26_sku-barcode-inventory]]

## Summary

Replace immediate RTS stock-in writes with a persistent editable draft session. Continuous camera scans use presentation-edge detection rather than a time cooldown; inventory changes only when the user confirms the reviewed session.

## Completed work

### Data / backend

1. [x] Added RTS stock-in session, item, and audit tables in phase 76.
2. [x] Added owned draft start/get/scan/update/remove/discard helpers.
3. [x] Added row-locked revalidation, request idempotency, and atomic confirm.

### API / frontend

1. [x] Added typed session lifecycle actions to `api/v1/rts.php` and `rtsApi.ts`.
2. [x] Replaced immediate RTS stock-in with start/resume, review/edit/remove, cancel, and confirm.
3. [x] Added RTS-only presentation-edge camera scanning with no time cooldown.

### Verification

1. [x] Added and ran transaction-rollback regression coverage.
2. [x] Passed PHP syntax, SPA build, memory-bank generation, lint, and packaging regression.
3. [x] Added problem/cause/fix notes and regenerated maps.

## Rollback

Revert application files and leave the additive session/audit tables unused. No existing RTS inventory or barcode rows are rewritten by the migration.
