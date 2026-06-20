---
date: 2026-06-05
type: problem-record
status: fixed
area: parcel-pack-costing
tags: [problem, costing, parcel, session]
---

# Problem: Parcel pack costing ship bugs (500, ???, UX)

> Session rollup after Phase 1 ship. Links every bug → cause → fix so agents do not repeat.

## Symptom

1. **500 Server error** on Parcel packaging tab — tiers and landed columns failed to load.
2. **??? in labels** — `Honey ??? 1?? 1L`, duplicate cushion rows in tier editor.
3. **TBD tier preview error** — red banner when selecting 250/350 solo tier.
4. **Unprofessional / too long UI** — full-page tier cards buried the landed-cost product list executives need.

## Causes

| # | Issue | Cause note |
|---|--------|------------|
| 1 | API 500 | [[causes/parcel-pack-closure-missing-pdo-use]] |
| 2 | Missing include on preview | [[causes/parcel-pack-missing-dept-inventory-require]] |
| 3 | `???` labels + duplicate lines | [[causes/sql-migration-powershell-utf8-corruption]] |
| 4 | TBD preview 500 | [[causes/parcel-tier-inactive-preview-500]] |

## Fixes

| # | Fix note |
|---|----------|
| 1 | [[fixes/parcel-pack-pdo-closure-500]] |
| 2 | `require_once dept_inventory.php` in `parcel_pack_costing.php` |
| 3 | [[fixes/parcel-pack-ascii-labels-migration]] — run **phase31** |
| 4 | [[fixes/parcel-pack-tbd-tier-preview]] |
| 5 | [[fixes/parcel-pack-landed-view-modal]] |
| Feature | [[fixes/online-parcel-pack-costing]] (original ship) |

## Files

| Role | Path |
|------|------|
| Backend | `includes/parcel_pack_costing.php` |
| API | `api/v1/costing.php` |
| Migrations | `sql/migration_phase30_parcel_pack_costing.sql`, `sql/migration_phase31_parcel_pack_ascii_fix.sql` |
| SPA | `ParcelPackTiersModal.tsx`, `CostingPage.tsx`, `CostingParcelMetrics.tsx` |
| Spec | [[specs/2026-06-05_online-parcel-pack-costing]] |

## Deploy checklist

1. Run phase30 then **phase31** on any DB that saw `???` labels.
2. Windows SQL: `Get-Content ... -Encoding UTF8 -Raw | mysql ...`
3. `cd app && npm run build` + hard refresh.
4. Smoke: Costing → Parcel packaging → landed columns load; open **Tier rules** modal.

## AI rules (do not repeat)

- **Never** use Unicode punctuation in SQL seed strings on this repo (ASCII `-` and `x` only).
- **`static` closures** must `use ($pdo)` when calling helpers that need PDO.
- **Parcel tab UX:** landed product list is primary; tier editor belongs in a **modal** (`ParcelPackTiersModal`).
- **Inactive / TBD tiers:** preview must return warning, not 404/500.
- After shared `includes/` edits: regenerate memory bank + append [[002_Memory_Bank]].

## Links

- **Fragile edges:** [[003_Fragile_Edges#Parcel pack costing]]
- **Patterns:** [[005_SPA_UX_Patterns#Product Costing page]]
