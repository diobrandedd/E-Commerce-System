---
date: 2026-09-10
type: problem-record
status: fixed
area: honey-inventory
tags: [honey, production, warehouse, progress, overdrawn]
---

# Problem: Honey lot gallons hard to read; short production scan blocked

## Symptom

Production/Warehouse gallon columns showed bare numbers. Bottle scan into packaging blocked when the selected lot did not have enough gallons, stopping packaging work.

## Cause

→ [[causes/honey-lot-qty-no-progress]]

## Fix

→ [[fixes/honey-lot-gallon-progress-overdraw]]

## Files

| Role | Path / note |
|------|-------------|
| Deduct | `includes/honey_inventory.php` — allow negative production gallons + warning |
| SPA progress | `app/src/app/components/honey/HoneyLotGallonProgress.tsx` |
| Pages | `HoneyProductionPage.tsx`, `HoneyWarehousePage.tsx` |
| Scan warn | `HoneyBottleBarcodePanels.tsx` |
| Status | `app/src/app/lib/honey.ts` — overdrawn level |
| Spec | [[specs/2026-09-10_honey-lot-gallon-progress]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Honey production gallon overdraw]]
