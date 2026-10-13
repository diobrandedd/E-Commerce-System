---
date: 2026-10-09
type: problem-record
status: fixed
area: inventory
tags: [problem, supplies, packaging, nav]
---

# Problem: Packaging Materials and Supplies were two confusing inventory pages

## Symptom

Inventory nav showed both **Packaging Materials** and **Supplies**. Ops wanted a single **Supplies** page (the packaging catalog), and Add item should not ask for a department.

## Cause

→ [[causes/packaging-supplies-dual-nav]]

## Fix

→ [[fixes/supplies-label-packaging-catalog]]

## Files

| Role | Path / note |
|------|-------------|
| Nav / routes | `Sidebar.tsx`, `routes.ts`, `permissions.ts` |
| Page copy | `PackagingMaterialsPage.tsx`, guides/tours |
| Add item | `MaterialCatalogModal.tsx`, `material_inventory_create()` |
| Docs | `inventory-roles-and-guides.mdc`, `005_SPA_UX_Patterns.md` |

## Deploy

- Rebuild SPA + hard refresh
- PHP change for catalog create (no department required)

## AI rules

SPA **Supplies** = packaging catalog at `/inventory/supplies`. Do not resurrect the old `SuppliesMaterialsPage` route. Backend `stocks.category` stays `packaging`.
