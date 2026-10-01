---
date: 2026-10-01
type: problem-record
status: fixed
area: honey-packaging
tags: [problem]
---

# Problem: Packaging “Scanned by person” hid variant and size

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Stocked-into-inventory person rollup showed only total bottles (e.g. 14,890) with no Amb/Light/Dark or Liter/250ml breakdown.

## Cause

→ [[causes/honey-packaging-scanner-totals-only]]

## Fix

→ [[fixes/honey-packaging-scanner-variant-size-lines]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` — `honey_bottle_packaging_scan_report` `by_scanner.lines` |
| Client | `app/src/app/lib/bottleBarcodes.ts` |
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| Test | `tests/inventory/raw-materials.spec.ts` |
| Related | [[generated/modules/honey_bottle_barcodes]] |

## Deploy

- PHP + SPA build + hard refresh; regenerate memory bank

## Links

- **Related:** [[problems/records/2026-10-01_honey-production-stock-out-actor]]
