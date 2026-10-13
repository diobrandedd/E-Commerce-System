---
date: 2026-10-13
type: problem-record
status: fixed
area: inventory-check
tags: [inventory-check, history, filter]
---

# Problem: Check inventory history could not be filtered by date

## Symptom

Audit history and confirmed inventory updates listed every session, paged, with no way to show one day or a custom range.

## Cause

`inventory_check_event_list` and `inventory_check_applied_runs_list` only accepted page and owner scope.

## Fix

Optional `from` / `to` (`YYYY-MM-DD`) on `event_list` and `applied_runs_list`. History filters `count_date`. Confirmed updates filter the applied date. History UI: All dates, One day, or Custom range.

## Files

| Role | Path |
|------|------|
| Include | `includes/inventory_check.php` |
| API | `api/v1/inventory_check.php` |
| SPA | `InventoryCheckPage.tsx`, `app/src/app/lib/inventoryCheck.ts` |
