---
status: shipped
spec: "[[specs/2026-09-10_paragis-bag-pool-pouch-scan]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Paragis bag pool → pouch scan

> Spec: [[specs/2026-09-10_paragis-bag-pool-pouch-scan]]

## Summary

Hide Paragis WIP actions. Stock-in feeds the raw bag pool; pouch (and PT SKU) stock-in deducts leaf grams from that pool using Settings BOM (`pt.grams_per_tea_bag` × `pt.tea_bags_per_pouch`). Allow negative with warning. Show a bag-pool progress strip on the Paragis page.

## Implementation steps

### Phase 1 — Settings + BOM helpers

1. Add `includes/dept_pt_settings.php` (or extend `dept_products.php`) reading/writing `app_settings` keys; defaults from `PT_*` constants.
2. API: `settings.php` `pt_bom_get` / `pt_bom_save` (executive).
3. Wire `dept_pt_grams_per_tea_bag($pdo)`, `dept_pt_tea_bags_per_pouch($pdo)`, `dept_pt_grams_per_pouch($pdo)`.

### Phase 2 — Pool deduct

1. Extend snapshot with pool grams remaining, gross capacity, BOM fields.
2. `dept_raw_deduct_bags_allow_negative` — FIFO then overdraft entry; returns `{ bags_deducted, bags_on_hand, warned }`.
3. Call from `dept_pouch_scan_stock_in` when `productKey === 'pt'` and from `sku_barcode_dept_stock_in` for `pt`.

### Phase 3 — SPA

1. SettingsPage: Paragis BOM fields.
2. RawDeptMaterialPage: hide Use raw bags / WIP waste / WIP pouch; bag pool progress; copy update; scan warning UI.
3. Guides/tour copy update.

### Phase 4 — Verify

1. Playwright: Paragis pool UI + hidden WIP actions (mocked API).
2. `verify.ps1`; memory bank; archive plan.

## Approval

- [x] Human approved — OK to implement
- Approved date: 2026-09-10
