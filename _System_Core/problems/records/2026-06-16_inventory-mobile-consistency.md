---
date: 2026-06-16
type: problem-record
status: fixed
area: inventory, mobile-ux
tags: [problem, mobile, inventory, packaging, supplies]
---

# Problem: Dense inventory pages were inconsistent on phone

## Symptom

Packaging and Supplies pages still used desktop-style header actions and dense table rows on narrow screens, making controls crowded and row details hard to scan.

## Cause

Phone-first pattern was applied only on raw materials page; other inventory pages kept mixed responsive row logic without dedicated mobile action surface.

## Fix

→ [[fixes/inventory-mobile-action-modal-and-cards]]

## Files

| Role | Path / note |
|------|-------------|
| Packaging page | `app/src/app/pages/inventory/PackagingMaterialsPage.tsx` |
| Supplies page | `app/src/app/pages/inventory/SuppliesMaterialsPage.tsx` |
| Pattern note | [[005_SPA_UX_Patterns#Packaging and materials inventory pages]] |

## Deploy

- SPA build + hard refresh

## Links

- **Rule:** [[005_SPA_UX_Patterns#Golden rules]]
- **Raw page baseline:** [[records/2026-06-16_raw-dept-mobile-layout]]
