---
date: 2026-06-11
type: problem-record
status: fixed
area: inventory
tags: [problem, ux]
---

# Problem: Packaging page and detail modal grew with many rows

> Whole page scrolled; activity list stretched modals; HR Requests buried under Inventory.

## Symptom

Adding many packaging catalog rows made the entire `PageLayout` main scroll. Detail modal Recent activity grew without limit. HR had to open Inventory dropdown to reach Requests.

## Cause

List and modal used document flow without `min-h-0` flex constraints; Requests was only a child of `inventoryNav` for all roles.

## Fix

→ [[fixes/packaging-inventory-scroll-and-detail-ux]]

- Table body scroll inside fixed viewport (`h-[calc(100dvh-8rem)]`)
- Modal activity `max-h-44 overflow-y-auto`; modal body `overflow-hidden`
- HR: top-level `requestsNav` in `Sidebar.tsx`

## Files

| Role | Path |
|------|------|
| SPA | `PackagingMaterialsPage.tsx`, `PackagingMaterialDetailModal.tsx`, `Sidebar.tsx` |
| Patterns | [[005_SPA_UX_Patterns#Packaging and materials inventory pages]] |

## AI rules

Reuse scroll + detail modal patterns from the fix note when building Supplies or other material catalog pages — do not let `PageLayout` main be the only scroll for long tables.
