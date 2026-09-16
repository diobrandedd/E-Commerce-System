---
date: 2026-09-16
type: problem-record
status: fixed
area: dept-raw
tags: [problem]
---

# Problem: No executive gram edit on PT/BRC/Cacao raw pools

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Executives could correct honey production gallons on the Production page, but Paragis / BRC / Jar Cacao leaf pools on raw materials pages were read-only aside from stock-in/waste. Monthly Inventory could set kg only with admin password — not day-to-day grams.

## Cause

→ [[causes/dept-raw-no-executive-grams]]

## Fix

→ [[fixes/dept-raw-executive-set-grams]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/dept_inventory.php` (`dept_raw_set_grams_on_hand`) |
| API | `api/v1/honey.php` (`dept_raw_executive_set`) |
| SPA | `ExecutiveRawPoolEditModal.tsx`, `RawDeptMaterialPage.tsx`, `deptRawInventory.ts` |
| Related | [[generated/modules/dept_inventory]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory / stock truth]]
- **Related:** [[problems/records/2026-09-15_raw-stock-in-kilos]]
