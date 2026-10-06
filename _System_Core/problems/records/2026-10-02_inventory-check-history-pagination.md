---
date: 2026-10-02
type: problem-record
status: fixed
area: inventory-check
tags: [problem]
---

# Problem: Check inventory history lists were too long to scroll

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

All audit history and confirmed inventory updates rendered ~25+ rows each with no next/previous controls, making the History view overly long.

## Cause

→ [[causes/inventory-check-history-no-pager]]

## Fix

→ [[fixes/inventory-check-history-pagination]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `app/src/app/pages/inventory/InventoryCheckPage.tsx` |
| Client | `app/src/app/lib/inventoryCheck.ts` — `eventList` / `appliedRunsList` per_page=10 |
| API | Existing `event_list` / `applied_runs_list` pagination (unchanged) |

## Deploy

- SPA build + hard refresh

## Links

- **Related:** [[problems/records/2026-09-17_inventory-check-applied-run-history]]
