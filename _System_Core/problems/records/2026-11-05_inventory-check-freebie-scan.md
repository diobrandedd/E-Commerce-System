---
date: 2026-11-05
type: problem-record
status: fixed
area: inventory-check
tags: [problem, freebie, barcode, audit]
---

# Problem: Freebie barcodes not usable in Check inventory scan

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Check inventory Add modal only treated SKU and unique stickers. Scanning a freebie code (FRB-PT / FRB-BRC / FRB-CACAO) showed "Unknown barcode." Freebies were counted only via a separate manual Freebie tab.

## Cause

→ [[causes/inventory-check-freebie-scan-gap]]

## Fix

→ [[fixes/inventory-check-freebie-scan]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `app/src/app/pages/inventory/InventoryCheckPage.tsx` |
| SPA API | `app/src/app/lib/inventoryCheck.ts` (removed `scanFreebie`) |
| Backend (unchanged) | `includes/inventory_check.php` already maps freebie barcodes to `FREEBIE:{key}` |

## Deploy

- `cd app && npm run build` + hard refresh

## Links

- **Spec:** [[specs/2026-10-30_freebie-barcodes]]
- **Related:** [[problems/records/2026-10-30_freebie-barcodes]]
