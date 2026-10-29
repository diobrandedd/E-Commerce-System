---
date: 2026-10-29
type: problem-record
status: fixed
area: packaging-supplies
tags: [supplies, barcode, print]
---

# Problem: Supplies barcode sheet always printed the whole catalog

> Executives need a sheet of only the supplies they tick, including ticks made in more than one category.

## Symptom

Generate all barcodes printed every supply. There was no way to choose rows, and a filter change would have dropped a temporary selection if it lived only on the visible list.

## Cause

→ [[causes/packaging-no-damage-pool]]

## Fix

→ [[fixes/supplies-print-selected-barcodes]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `PackagingMaterialsPage.tsx`, `app/src/app/lib/materials.ts` |
| API | `api/v1/materials.php` `packaging_print_sheet&ids=` |
| Includes | `includes/material_inventory.php` `material_packaging_print_sheet_html` |

## Deploy

- `cd app && npm run build` + hard-refresh Apache `app/dist/`
