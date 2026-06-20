---
date: 2026-06-16
type: problem-record
status: fixed
area: dept-raw-production, mobile-ux
tags: [problem, mobile, inventory, raw-materials, ux]
---

# Problem: Raw materials page broke on phone layout

## Symptom

On mobile, the raw materials page header became crowded and hard to use (title/actions overlap), and activity tables were too dense to read.

## Cause

→ [[causes/raw-page-header-actions-overflow-mobile]]

## Fix

→ [[fixes/raw-dept-mobile-first-actions-and-lists]]

## Files

| Role | Path / note |
|------|-------------|
| Page | `app/src/app/pages/inventory/RawDeptMaterialPage.tsx` |
| Cause | [[causes/raw-page-header-actions-overflow-mobile]] |
| Fix | [[fixes/raw-dept-mobile-first-actions-and-lists]] |
| Patterns | [[005_SPA_UX_Patterns#Dept raw production (Paragis / BRC / Jar)]] |

## Deploy

- SPA build + hard refresh

## Links

- **Fragile edges:** [[003_Fragile_Edges#Mobile-first SPA traps]]
- **Patterns:** [[005_SPA_UX_Patterns#Golden rules]]
