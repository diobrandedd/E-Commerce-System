# Fix: Paragis vs BRC & Cacao costing / parcel recipe buckets

> Session detail: [[specs/2026-06-18_costing-parcel-packaging-session]]

## User requirement

- **Paragis Tea** = own department in costing UI.
- **BRC + Jar Cacao** = same department (`cacao_brc`) — matches inventory hub `CacaoBrcHubPage.tsx`.
- Excel combos: PT, BRC, and Cacao each ship with **1L / 350ml / 250ml honey** — must appear under the correct dept in Parcel recipes, not only under Honey.

## Cause

UI mirrored legacy `fulfillment_pack_tiers.family` (`dept_pt_brc`, `jar_cacao`) instead of ops departments. Combo tiers intentionally use `family: honey` for match-rule priority but belong to PT, BRC, or Cacao by `tier_key` prefix.

## Fix

| Layer | Change |
|-------|--------|
| `parcelRecipeBuckets.ts` | `parcelRecipeDeptBucket(tier)` + `PARCEL_RECIPE_DEPT_LABELS` |
| `costingProducts.ts` | `splitDeptCatalogProducts()` — `pt` vs `brc`/`jar_cacao` |
| `CostingPage.tsx` | Chips: Paragis Tea, BRC & Cacao; two product sections |
| `CostingParcelRecipesPanel.tsx` | Filter/display by dept bucket |
| `ParcelPackTiersModal.tsx` | Section headers by dept bucket |
| `CostingGuideModal.tsx` | Copy for combos + dept split |

## Combo tier keys (from Excel)

- PT: `combo_pt_1L_honey`, `combo_pt_350ml_honey`, `combo_pt_250ml_honey`
- BRC: `combo_brc_1L_honey`, `combo_brc_350ml_honey`, `combo_brc_250ml_honey`
- Cacao: `combo_cacao_1L_honey`, `combo_cacao_350ml_honey`, `combo_cacao_250ml_honey`

## Do not

- Merge BRC into Paragis filter chip.
- Filter combos with `tier.family === 'honey'` only — use bucket helper.
