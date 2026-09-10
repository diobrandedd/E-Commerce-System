---
status: shipped
spec: "[[specs/2026-09-10_brc-bag-pool-pouch-scan]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Black Rice bag pool → pouch scan

> Spec: [[specs/2026-09-10_brc-bag-pool-pouch-scan]]

## Summary

Mirror Paragis bag-pool for BRC: Settings BOM (`brc.grams_per_pouch`, `brc.grams_per_bag`), snapshot + pouch/SKU scan deduct via existing `dept_raw_deduct_bags_allow_negative`, progress strip UI, hide inventorial Produce as primary path.

## Implementation steps

### Phase 1 — Settings + BOM helpers

1. Add `includes/dept_brc_settings.php` (parallel to `dept_pt_settings.php`): read/write `brc.grams_per_pouch`, `brc.grams_per_bag`; defaults from `BRC_*` constants; `bags_per_pouch = grams_per_pouch / grams_per_bag`.
2. API: `settings.php` `brc_bom_get` / `brc_bom_save` (executive).
3. SettingsPage: Black Rice BOM section next to Paragis.

### Phase 2 — Pool deduct + snapshot

1. Extend `dept_raw_detail` / enriched snapshot for `brc` with `bag_pool` + `bag_pool_workflow: true` (same shape as PT).
2. Call deduct from `dept_pouch_scan_stock_in` when `productKey === 'brc'` and from `sku_barcode_dept_stock_in` for `brc`.
3. No packaging deduct on scan.

### Phase 3 — SPA

1. Generalize `ParagisBagPoolStrip` → shared `DeptBagPoolStrip` (or prop label) usable for PT + BRC.
2. `RawDeptMaterialPage`: show pool for BRC; Do next copy; hide/demote Produce pouches for bag-pool products; update black-rice route subtitle; guide/tour if present.
3. Scan warning UI already shared via bag_pool payload — verify BRC path.

### Phase 4 — Verify

1. Playwright: BRC pool UI (mocked API) + 100 g label.
2. `verify.ps1`; memory bank; problem record; archive plan.

## Out of scope

- Jar Cacao bag pool
- Historical produce_brc cleanup

## Approval

- [x] Human approved — OK to implement
- Approved date: 2026-09-10
