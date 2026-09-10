---
status: shipped
spec: "[[specs/2026-09-10_jar-cacao-bag-pool-jar-scan]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Jar Cacao bag pool → jar scan

> Spec: [[specs/2026-09-10_jar-cacao-bag-pool-jar-scan]]

## Summary

Mirror BRC bag-pool for Jar Cacao: Settings BOM (`jar_cacao.grams_per_jar`, `jar_cacao.grams_per_bag`), snapshot + jar/SKU scan deduct via `dept_raw_deduct_bags_allow_negative`, progress strip with jar labels, hide inventorial Produce.

## Implementation steps

### Phase 1 — Settings

1. `includes/dept_jar_cacao_settings.php` parallel to `dept_brc_settings.php`.
2. `settings.php` `jar_cacao_bom_get` / `jar_cacao_bom_save`.
3. SettingsPage: Jar Cacao BOM (g per jar + g per bag).

### Phase 2 — Pool deduct + snapshot

1. `bag_pool` on `jar_cacao` detail; `bag_pool_workflow: true`; `produce_action: null`.
2. Deduct from `dept_pouch_scan_stock_in` and `sku_barcode_dept_stock_in` for `jar_cacao` (0.22 bags at defaults; DECIMAL(14,6) already).
3. No packaging deduct.

### Phase 3 — SPA

1. Extend `DeptBagPoolStrip` sell-unit label (`pouch` vs `jar`).
2. `RawDeptMaterialPage`: bag-pool for cacao; daily jar record; subtitle; guide/tour.
3. Playwright mocked cacao pool UI (220 g / jar).

### Phase 4 — Verify

`verify.ps1`; memory bank; problem record; archive plan.

## Approval

- [x] Human approved — OK to implement
- Approved date: 2026-09-10
