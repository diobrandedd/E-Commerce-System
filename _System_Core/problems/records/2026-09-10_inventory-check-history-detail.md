---
date: 2026-09-10
type: problem-record
status: fixed
area: inventory-check
tags: [problem, inventory-check, history, modal]
---

# Problem: Check inventory history could not open past sessions

## Symptom

Audit history listed dates and counts but had no way to view counted products, comparison variance, or who scanned what for a closed session.

## Cause

→ [[causes/inventory-check-history-no-detail]]

## Fix

→ [[fixes/inventory-check-history-detail]]

## Files

| Role | Path / note |
|------|-------------|
| SPA page | `app/src/app/pages/inventory/InventoryCheckPage.tsx` |
| Modal | `app/src/app/components/inventory/InventoryCheckHistoryDetailModal.tsx` |
| Existing log | `app/src/app/components/inventory/InventoryCheckScanLogModal.tsx` |

## Deploy

- `cd app && npm run build` + hard refresh
- No migration / API contract change (read-only use of existing actions)

## Links

- **Module:** [[generated/modules/inventory_check]]
