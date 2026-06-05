---
status: approved
spec: "[[specs/2026-06-05_RTS-SPA-Migration]]"
created: 2026-06-05
approved_by: user
---

# Implementation Plan: RTS SPA migration

## Summary

Extract case handlers to `includes/rts_case_handlers.php`, add `api/v1/rts_cases.php` (IMS + CSRF), build three SPA pages, fix routes and legacy redirects.

## Implementation steps

### Phase 1 — Backend
- [x] `includes/rts_case_handlers.php`
- [x] Refactor `api/case.php`
- [x] `api/v1/rts_cases.php`
- [x] Widen `rts_sync` roles

### Phase 2 — Frontend
- [x] `rtsApi.ts`, `rtsProducts.ts`
- [x] `RTSRecordPage`, `RTSRecordsPage`, `RTSDashboardPage`
- [x] Routes + Sidebar + legacy redirects

### Phase 3 — Close
- [x] Memory bank + fragile edges
