# Fix: Parcel packaging UX — landed list primary, tiers in modal

**Date:** 2026-06-05

## Symptom

Parcel tab was too long (tier editor cards + preview above product list). Executives mainly need **landed cost by product** (COGS + pack + margin).

## Fix

- **Parcel packaging tab:** compact `ParcelPackLandedIntro` + product table with `CostingParcelMetrics` columns.
- **Tier rules & calculator:** `ParcelPackTiersModal` (tabs: Try an order | Edit tier rules).
- Column **Pack (typ.)** replaces "Parcel"; removed per-row `ref:` scenario clutter.
- **New Product** hidden on parcel tab.

**Pattern:** [[005_SPA_UX_Patterns#Product Costing page]]

## Files

- `app/src/app/components/costing/ParcelPackTiersModal.tsx` (replaces `ParcelPackCostingPanel.tsx`)
- `app/src/app/pages/exec/CostingPage.tsx`
- `app/src/app/components/costing/CostingParcelMetrics.tsx`
