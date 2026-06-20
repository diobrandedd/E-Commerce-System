---
date: 2026-06-18
type: problem-record
status: fixed
area: fulfillment-packaging
tags: [problem, insulation-foam, costing, ui]
---

# Problem: Cacao parcel foam cost showed full roll price (₱166+) per jar

## Symptom

`cacao_1_jar` parcel recipe listed insulation foam at ₱166.67/foam_piece — entire roll WAC. Jar Cacao landed/pack cost looked ~₱171 instead of ~₱5–10.

## Cause

Sized foam SKUs had `pool_yield_qty = NULL`. Costing fell back to full generic roll price when yield missing. Sized SKUs hidden from Packaging Materials list — no UI to configure yields.

## Fix

→ [[fixes/foam-cacao-cut-yields-on-generic-roll]]

## Files

| Role | Path |
|------|------|
| Backend | `includes/material_inventory.php`, `api/v1/materials.php` |
| SPA | `PackagingMaterialDetailModal.tsx`, `materials.ts` |

## Deploy

- `cd app && npm run build`
- Executive: open **Insulation foam roll** → set both cut yields → Save
