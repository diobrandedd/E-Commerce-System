---
date: 2026-09-16
type: problem-record
status: fixed
area: honey-packaging
tags: [problem]
---

# Problem: Packaging Edit did not change the numbers on screen

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Executive Edit on Raw Honey → Packaging updated `honey_packaging_rows.current_*`, but the table still showed gross scan counts, so reducing 135→130 looked like it did nothing.

## Cause

→ [[causes/honey-packaging-displayed-scanned-not-current]]

## Fix

→ [[fixes/honey-packaging-show-current-on-hand]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| Related | `ExecutiveRowEditModal.tsx` (packaging stage) |
| Test | `tests/inventory/raw-materials.spec.ts` |

## Deploy

- `cd app && npm run build` + hard refresh

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory / stock truth]]
