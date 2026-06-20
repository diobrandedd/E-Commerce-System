---
date: 2026-06-12
type: problem-record
status: fixed
area: raw-honey-inventory
tags: [honey, spa, ux]
---

# Problem: Raw Honey showed summary only — missing Warehouse / Production / Packaging tabs

## Symptom

Executive opened **Inventory → Raw Materials → Raw Honey** and saw a flat warehouse gallon table with no Warehouse / Production / Packaging tabs. Stock-in required following a small link to Products → Honey → Warehouse.

## Cause

Inventory arrangement shipped `RawHoneySummaryPage` as a read-only `fetchRawSnapshot()` view while the real honey workflow lived only under **Products → Honey** (`HoneyWarehousePage`, etc.). IA split confused users; fragile-edge doc even said “stock-in stays Products → Honey → Warehouse.”

## Fix

→ [[fixes/raw-honey-stage-tabs]]

## Files

| Role | Path |
|------|------|
| Tabs | `app/src/app/components/honey/HoneyStageTabs.tsx` |
| Stage pages | `app/src/app/pages/honey/HoneyWarehousePage.tsx` (+ production, packaging) |
| Routes / nav | `app/src/app/routes.ts`, `app/src/app/components/Sidebar.tsx` |

## Deploy

- `cd app && npm run build` + hard refresh (SPA only; no migration)

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Raw Honey (three stages)]]
- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement]]
