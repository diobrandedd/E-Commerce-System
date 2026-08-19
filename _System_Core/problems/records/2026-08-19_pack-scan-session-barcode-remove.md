---
date: 2026-08-19
type: problem-record
status: fixed
area: pack-scan
tags: [problem]
---

# Problem: Pack scan session rows did not show or allow removing barcodes

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Operators saw product counts in the live pack session but not the barcodes on those rows. A mistaken scan could not be removed without discarding the whole session.

## Cause

→ [[causes/pack-scan-session-counts-only]]

## Fix

→ [[fixes/pack-scan-session-barcode-remove]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` — `pack_remove_item` |
| SPA | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |
| SPA | `app/src/app/lib/bottleBarcodes.ts` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh. No SQL.

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Feedback animations & hover (mandatory)]]
