---
status: shipped
created: 2026-06-18
area: costing, fulfillment-packaging
tags: [costing, parcel-pack, excel, dept, combo, session-knowledge]
source_excel: "C:/Users/Admin/Downloads/Product packaging material list (2).xlsx"
---

# Costing + parcel packaging session (2026-06-18)

> **Canonical memory for this chat arc.** Read this before changing costing UI, parcel tiers, cushion/foam/bubble SKUs, or department grouping. Supplements [[specs/2026-06-17_fulfillment-packaging-recipes]] and [[specs/2026-06-18_bubble-wrap-staging-damage]].

## User goals (why we built this)

1. **Parcel packaging ≠ product COGS** — Bubble, cushions, pouches, foam are fulfillment materials deducted at **ship**, not duplicated in each product formula line.
2. **Real order shapes** — Multi-qty, combos (PT + honey, BRC + honey, Cacao + honey), bulk 350/250ml packs need **one matched parcel recipe** per shipped box, not per-SKU rollup only.
3. **Costing hub** — Switchable **Products** vs **Parcel recipes** tables; department filters; row click → detail modal with COGS, reference packaging, order calculator.
4. **Ops departments** — **Paragis Tea** is its own dept; **Black Rice Coffee (BRC) and Jar Cacao share `cacao_brc`** — never group BRC with Paragis in UI.

## Two cost layers (do not merge)

| Layer | What | When priced / deducted | Where in UI |
|-------|------|------------------------|-------------|
| **Manufacturing COGS** | Raw, production packaging (bottles, pouches, seals, tea bags) | Produce / bottling | Product formula in Costing |
| **Fulfillment packaging** | Bubble, cushion pieces/rolls, courier pouch, foam, cardboard | Parcel **ship** (staging consume) | Parcel recipes tab; reference add-on on product rows |

**Landed cost (reference)** = manufacturing COGS + **typical** parcel pack for that product family (solo reference tier — not every combo).

---

## Ops departments vs DB `family` enum

| Ops department | `dept_product_key` | Inventory hub | Parcel UI bucket |
|----------------|-------------------|---------------|------------------|
| Paragis Tea | `pt` | `/inventory/products/paragis-tea` | `paragis` |
| Black Rice Coffee | `brc` | `/inventory/cacao-brc` → BRC | `cacao_brc` |
| Jar Cacao | `jar_cacao` | `/inventory/cacao-brc` → Cacao | `cacao_brc` |

**Trap:** `parcel_pack_tiers.family` is still `honey | dept_pt_brc | jar_cacao` from phase 30. **Combo tiers use `family: honey`** for matcher priority but belong to PT/BRC/Cacao in UI by **`tier_key` prefix**. Never filter combos with `tier.family === 'dept_pt_brc'`.

**UI bucketing** — `app/src/app/lib/parcelRecipeBuckets.ts`:

```ts
parcelRecipeDeptBucket(tier):
  combo_pt_*           → paragis
  dept_pt_*            → paragis
  combo_brc_*          → cacao_brc
  combo_cacao_*        → cacao_brc
  dept_brc_*           → cacao_brc
  jar_cacao / cacao_*  → cacao_brc
  else                 → honey
```

Products tab split — `splitDeptCatalogProducts()` in `costingProducts.ts`: `pt` → Paragis section; `brc` + `jar_cacao` → BRC & Cacao section.

---

## Source spreadsheet (Excel)

**File:** `Product packaging material list (2).xlsx` (user Downloads)  
**Reader tool:** `php tools/read_packaging_xlsx.php` (PhpSpreadsheet; truncates at 60 rows per sheet)

### Column meanings

| Excel column | Maps to stock / unit |
|--------------|---------------------|
| Cushion type (customize) | Bought pieces: `Cushion piece - 1/2/3 liter`, `LAD`, `honey comb`, half-LAD (= 0.5 LAD) |
| Cushion Type (roll) | `Cushion roll - main` columns (GAL bulk) |
| Cushion roll (Divider) | `Cushion roll - divider` columns |
| Cardboard Box | `Cardboard box - whole` (HALF = 0.5) |
| Bubble Wrap size and quantity | Sized rolls as **pieces** (`bubble_piece`); costing uses **generic** `Bubble wrap roll` WAC ÷ yield |
| Courier Pouch | `Courier pouch - medium/large/x-large` |
| Insulation foam for Cacao Jar | `Insulation foam roll - 9x12` / `12.5x12` as `foam_piece`; warehouse stocks **generic** `Insulation foam roll` |

### Bubble yields (Excel rows 49–52)

| Width | Pieces per roll (`pool_yield_qty`) |
|-------|-----------------------------------|
| 13 cm | 264 |
| 17 cm | 204 |
| 22 cm | 136 |

---

## Excel row → `tier_key` mapping

### Honey solo & bulk

| Excel row | Item/s | `tier_key` |
|-----------|--------|------------|
| 2 | 1 Liter Honey | `honey_1x_1L` |
| 3 | 2 Liter Honey | `honey_2x_1L` |
| 4 | 2 Liter Honey + 250ml | `honey_2x_1L_250ml` |
| 5 | 3 Liter Honey | `honey_3x_1L` |
| 6 | 3 Liter Honey + 350ml | `honey_3x_1L_350ml` |
| 7 | 3 Liter Honey + Cacao | `combo_cacao_1L_honey_3L_cacao` |
| 8 | 350ml Honey | `honey_350ml_solo` (half LAD piece) |
| 9 | 3x 350ml Honey | `honey_3x_350ml` |
| 10 | 250ml Honey | `honey_250ml_solo` |
| 11 | 1 Gallon Honey | `honey_1x_gallon` |
| 12 | 2 Gallon Honey | `honey_2x_gallon` |
| 13 | HONEY COMB | `honey_comb_solo` |
| 38–41 | 18/21/24/36 pcs 350 ml | `bulk_18x_350ml` … `bulk_36x_350ml` |
| 44–46 | 21/24/42 pcs 250 ml | `bulk_21x_250ml` … `bulk_42x_250ml` |

### Paragis Tea

| Excel row | Item/s | `tier_key` | Materials (seed) |
|-----------|--------|------------|-------------------|
| 15 | 1-2x Paragis Tea | `dept_pt_1_2` | 1L cushion + medium pouch |
| 16 | 3-5x Paragis Tea | `dept_pt_3_5` | LAD + large pouch |
| 17 | PT + 1 Liter Honey | `combo_pt_1L_honey` | LAD + large pouch (no bubble per Excel) |
| 18 | PT + 350ml Honey | `combo_pt_350ml_honey` | LAD + large |
| 19 | PT + 250ml Honey | `combo_pt_250ml_honey` | LAD + large |
| 20 | 10-12x Paragis Tea | `dept_pt_10_12` | 2× 3L cushion + bubble + x-large |

### BRC (same dept as Cacao in ops, separate tiers)

| Excel row | Item/s | `tier_key` | Materials (seed) |
|-----------|--------|------------|-------------------|
| 22 | 1-2x BRC | `dept_brc_1_2` | 1L cushion + medium |
| 23 | 3-5x BRC | `dept_brc_3_5` | LAD + large |
| 24 | BRC + 1 Liter Honey | `combo_brc_1L_honey` | 1L cushion + 17cm + 13cm bubble |
| 25 | BRC + 350ml Honey | `combo_brc_350ml_honey` | LAD + large |
| 26 | BRC + 250ml Honey | `combo_brc_250ml_honey` | LAD + large |

### Jar Cacao

| Excel row | Item/s | `tier_key` | Foam |
|-----------|--------|------------|------|
| 28 | 1x Jar Cacao | `cacao_1_jar` | 9×12 |
| 29 | 2x Jar Cacao | `cacao_2_jars` | 12.5×12 |
| 30 | 3-4x Jar Cacao | `cacao_3_4_jars` | 2× 12.5×12 |
| 31 | 3-5x Jar Cacao | `cacao_5_jars` | 2× 12.5×12 + 9×12 |
| 32 | Cacao + 1 Liter Honey | `combo_cacao_1L_honey` | 2L cushion + bubble + 9×12 |
| 33 | Cacao + 350ml Honey | `combo_cacao_350ml_honey` | LAD + large + 9×12 |
| 34 | Cacao + 250ml Honey | `combo_cacao_250ml_honey` | LAD + large + 9×12 |

**Seed file:** `includes/fulfillment_pack_tier_seed.php` — reseed with `php tools/seed_fulfillment_pack_tiers.php`.

---

## Matcher behavior (ship + costing preview)

1. Parse `Item/s` → shape: `honey_1L_count`, `honey_250ml`, `honey_350ml`, `dept_pt`, `dept_brc`, `jar_units`, …
2. Walk `parcel_pack_tiers` **ORDER BY sort_order** — first match wins (**one tier**, not sum of families).
3. Priority bands in seed: bulk (10) → comb (20) → combos (30–39) → honey solo (60+) → dept solo (100+) → cacao (130+).
4. Combo rules require `combo: true` and explicit honey size fields; solo rules use `solo_honey`, `solo_dept_pt`, `solo_dept_brc`, `solo_jar`.

Unmatched shape → warning + block ship.

---

## Reference parcel add-on per product (landed column)

`parcel_pack_reference_addon_for_product()` in `includes/parcel_pack_costing.php`:

| Product | Reference `tier_key` | Scenario label |
|---------|---------------------|----------------|
| Honey 250ml | `honey_250ml_solo` | solo 250ml |
| Honey 350ml | `honey_350ml_solo` | solo 350ml |
| Honey liter | `honey_1x_1L` | solo 1×1L |
| Honey gallon | `honey_1x_gallon` | solo 1 gallon |
| PT | `dept_pt_1_2` | PT solo 1–2 units |
| BRC | `dept_brc_1_2` | BRC solo 1–2 units |
| Jar Cacao | `cacao_1_jar` | Cacao solo 1 jar |

Combo orders use **Parcel recipes** tab or product modal **Try an order** — not the solo reference tier on the product row.

---

## Costing hub UI (shipped 2026-06-18)

### Mode toggle

| Mode | Component | Purpose |
|------|-----------|---------|
| **Products** | `CostingPage.tsx` | Dept chips, landed columns, click → `CostingProductDetailModal` |
| **Parcel recipes** | `CostingParcelRecipesPanel.tsx` | Full tier table + `CostingOrderCalculator` + `CostingParcelRecipeDetailModal` |

### Product detail modal tabs

1. **Manufacturing** — formula lines, COGS
2. **Reference packaging** — solo tier_key + material breakdown
3. **Try an order** — paste Item/s, preview matched tier

### Dept filter chips (Products)

`All | Honey | Paragis Tea | BRC & Cacao | Other`

### Dept filter chips (Parcel recipes)

Same three buckets via `parcelRecipeDeptBucket()` — **not** `tier.family`.

### Edit tier rules

`ParcelPackTiersModal` — grouped by honey / paragis / cacao_brc buckets.

### Motion / UX

Per [[005_SPA_UX_Patterns]] — `appHoverRow`, `AppErrorBanner`, `AppModal`, etc.

---

## Migrations & tools (this session)

| Phase | File | What |
|-------|------|------|
| 38 | `migration_phase38_bubble_wrap_staging_damage.sql` | Generic `Bubble wrap roll` warehouse; sized staging; `damaged_qty` |
| 39 | `migration_phase39_cleanup_legacy_packaging.sql` | Deactivate legacy parcel SKUs; sync helper |
| 40 | `migration_phase40_custom_cushion_pieces.sql` | Reactivate bought `Cushion piece - 1/2/3 liter` |
| 41 | `migration_phase41_insulation_foam_staging.sql` | Generic `Insulation foam roll`; sized yield-only SKUs |

| Tool | Purpose |
|------|---------|
| `tools/seed_fulfillment_pack_tiers.php` | Upsert tiers from seed PHP |
| `tools/sync_all_costing_from_prices.php` | Supplier + packaging WAC → product baselines |
| `tools/read_packaging_xlsx.php` | Dump Excel for alignment checks |

**Windows SQL:** use `Get-Content -Encoding UTF8 -Raw file.sql | mysql …` — not `<` redirection. Use `;` not `&&` in PowerShell.

---

## Errors hit during implementation (learn from these)

| Error | Cause | Fix |
|-------|-------|-----|
| MySQL import failed on Windows | PowerShell `<` redirect | `Get-Content … \| mysql` |
| `StrReplace` failed on tier seed | Missing `path` param in tool call | Always pass file path |
| CostingPage broke after refactor | Accidentally removed `guideOpen` / `hubMode` state | Restore state; extract `renderDeptProductRow` |
| Combo tiers invisible under Paragis filter | Filtered on `tier.family === 'dept_pt_brc'` | Bucket by `tier_key` prefix |
| Tier labels `???` after SQL import | Non-ASCII in migration strings | ASCII-only labels; `migration_phase31` fix |
| Parcel API 500 on tier lines | Closure missing `use ($pdo)` | [[causes/parcel-pack-closure-missing-pdo-use]] |
| Insufficient staging on ship | PM never issued warehouse → packing | Issue generic bubble/foam rolls first (phase 34 staging) |

---

## Design choices (intentional — do not “fix” without user)

1. **DB `family` enum unchanged** — UI buckets decouple ops dept from legacy enum; avoids migration on 37+ tiers.
2. **Combos stay `family: honey`** — sort_order places combos before solo honey; matcher uses `combo: true` rules.
3. **BRC + Cacao same inventory hub** — `CacaoBrcHubPage.tsx`; separate **product keys** and **tier keys** still.
4. **Generic roll costing** — Sized bubble/foam lines in recipes; WAC from one warehouse roll ÷ `pool_yield_qty`.
5. **Bought liter cushions** — Excel “customize” column drives 1L/2L/3L **pieces**, not roll columns, for liter-sized solo/combo rows (phase 40).
6. **Product row reference pack** — Solo tier only; combos discovered via recipes tab / order calculator.
7. **Single tier per parcel** — Not additive sum of honey + dept + cacao tiers (supersedes old v1 mental model).

---

## Files touched (complete list for agents)

### Backend

- `includes/fulfillment_pack_tier_seed.php`
- `includes/parcel_pack_costing.php` (`parcel_pack_reference_addon_for_product`, generic bubble/foam costing)
- `includes/fulfillment_packing.php` (staging consume, foam cut)
- `includes/material_inventory.php`

### SPA — costing

- `app/src/app/pages/exec/CostingPage.tsx`
- `app/src/app/components/costing/CostingHoneyVariantGroups.tsx`
- `app/src/app/components/costing/CostingProductDetailModal.tsx`
- `app/src/app/components/costing/CostingParcelRecipesPanel.tsx`
- `app/src/app/components/costing/CostingParcelRecipeDetailModal.tsx`
- `app/src/app/components/costing/CostingOrderCalculator.tsx`
- `app/src/app/components/costing/ParcelPackTiersModal.tsx`
- `app/src/app/components/costing/CostingGuideModal.tsx`

### SPA — lib

- `app/src/app/lib/parcelRecipeBuckets.ts` **(new)**
- `app/src/app/lib/costingProducts.ts` **(new)** — `splitDeptCatalogProducts`
- `app/src/app/lib/parcelPackCosting.ts` — types; `PARCEL_FAMILY_LABELS` marked legacy

### SPA — inventory (related)

- `PackagingMaterialsPage.tsx`, `PackingMaterialsStagingPage.tsx`

---

## Problem records & fixes (graph nodes)

| Record | Fix note |
|--------|----------|
| [[problems/records/2026-06-18_costing-hub-parcel-recipes-table]] | [[fixes/costing-hub-products-and-parcel-recipes]] |
| [[problems/records/2026-06-18_costing-dept-paragis-vs-cacao-brc]] | [[fixes/costing-dept-paragis-cacao-brc-buckets]] |
| [[problems/records/2026-06-18_parcel-tier-bought-cushion-pieces]] | [[fixes/parcel-tier-bought-liter-cushion-pieces]] |
| [[problems/records/2026-06-18_insulation-foam-generic-roll]] | [[fixes/insulation-foam-generic-roll-ship-cut]] |
| [[problems/records/2026-06-18_bubble-wrap-staging-damage]] | [[specs/2026-06-18_bubble-wrap-staging-damage]] |
| [[problems/records/2026-06-18_costing-sync-and-sku-cleanup]] | phase 39 + sync tool |

---

## Deploy checklist

```powershell
# Migrations (if not on prod)
Get-Content -Encoding UTF8 -Raw sql\migration_phase38_bubble_wrap_staging_damage.sql | mysql -u root rts_system
Get-Content -Encoding UTF8 -Raw sql\migration_phase39_cleanup_legacy_packaging.sql | mysql -u root rts_system
Get-Content -Encoding UTF8 -Raw sql\migration_phase40_custom_cushion_pieces.sql | mysql -u root rts_system
Get-Content -Encoding UTF8 -Raw sql\migration_phase41_insulation_foam_staging.sql | mysql -u root rts_system

c:\xampp\php\php.exe tools\seed_fulfillment_pack_tiers.php
c:\xampp\php\php.exe tools\sync_all_costing_from_prices.php

cd app; npm run build
c:\xampp\php\php.exe tools\memory_bank\generate_all.php
```

Stock-in before meaningful pack costs: generic bubble roll, generic insulation foam roll, bought cushion pieces (1L/2L/3L/LAD), pouches, sized yields configured.

---

## AI coding rules (quick)

- Read this spec + [[003_Fragile_Edges#Parcel pack costing]] before tier/UI edits.
- **Never** group BRC with Paragis in costing filters — use `cacao_brc` bucket for BRC + Jar Cacao.
- **Never** filter combo tiers by `family` alone — use `parcelRecipeDeptBucket(tier)`.
- Align new tiers to Excel rows; run `read_packaging_xlsx.php` when user provides updated spreadsheet.
- Parcel materials ≠ formula lines — do not add bubble/cushion to product COGS without explicit user request.
- After `includes/` changes: `impact.php` + `generate_all.php` + problem record + [[002_Memory_Bank]] line.
