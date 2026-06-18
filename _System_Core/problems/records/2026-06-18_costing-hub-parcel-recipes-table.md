---
date: 2026-06-18
type: problem-record
status: fixed
area: costing
tags: [problem, parcel-pack, ui]
---

# Problem: Costing could not show parcel pack cost per order shape

> Users needed a table view of packaging costs with department filters and detail modals — not buried tier rules or misleading COGS expand.

## Symptom

Parcel packaging tab showed product COGS formula on expand. Multi-item order pack cost was only in Tier rules → Try an order. Product rows lacked reference pack for 250ml/350ml honey.

## Fix

→ [[fixes/costing-hub-products-and-parcel-recipes]]

## Session knowledge

→ [[specs/2026-06-18_costing-parcel-packaging-session]]

## Files

| Role | Path |
|------|------|
| SPA | `CostingPage.tsx`, `CostingParcelRecipesPanel.tsx`, `CostingProductDetailModal.tsx`, `CostingParcelRecipeDetailModal.tsx`, `CostingOrderCalculator.tsx` |
| Backend | `includes/parcel_pack_costing.php` (reference addon tier_key + honey sizes) |

## Deploy

- `cd app && npm run build`
