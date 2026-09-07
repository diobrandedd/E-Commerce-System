---
date: 2026-09-07
type: problem-record
status: fixed
area: deliveries
tags: [pack-scan, reporting]
---

# Problem: Pack scan had no daily packaging / RTS sellable count

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Operators could not see how many packaging vs RTS sellable units they had packed today on Deliveries → Pack scan.

## Cause

→ [[causes/pack-scan-no-daily-pool-totals]]

## Fix

→ [[fixes/pack-scan-today-pool-stats]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` (`honey_bottle_pack_session_today_stats`) |
| API | `api/v1/bottle_barcodes.php` (`pack_today_stats`) |
| SPA client | `app/src/app/lib/bottleBarcodes.ts` |
| SPA UI | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- Related: [[specs/2026-08-15_pack-scan-history]]
