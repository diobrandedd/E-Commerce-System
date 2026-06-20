---
date: 2026-06-18
type: problem-record
status: fixed
area: costing
tags: [problem, parcel-pack, ui, dept]
---

# Problem: Costing grouped BRC with Paragis Tea; combo honey tiers hidden under Honey filter

> Ops departments: **Paragis Tea** alone vs **BRC + Jar Cacao** (`cacao_brc`). Excel combos (PT/BRC/Cacao + 1L/350ml/250ml honey) must appear under the correct dept in Parcel recipes.

## Symptom

Products filter chip said "PT / BRC / Cacao" in one bucket. Parcel recipe filters used DB `family` enum, so `combo_pt_*`, `combo_brc_*`, and `combo_cacao_*` tiers (stored as `family: honey`) only showed under Honey — not under Paragis or BRC & Cacao.

## Fix

→ [[fixes/costing-dept-paragis-cacao-brc-buckets]]

## Session knowledge

→ [[specs/2026-06-18_costing-parcel-packaging-session]]

## Files

| Role | Path |
|------|------|
| SPA lib | `parcelRecipeBuckets.ts`, `costingProducts.ts` (`splitDeptCatalogProducts`) |
| SPA | `CostingPage.tsx`, `CostingParcelRecipesPanel.tsx`, `ParcelPackTiersModal.tsx`, `CostingGuideModal.tsx` |

## Deploy

- `cd app && npm run build`
