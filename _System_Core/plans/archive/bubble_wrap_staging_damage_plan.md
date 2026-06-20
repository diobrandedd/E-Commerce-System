---
status: archived
approved: true
created: 2026-06-18
completed: 2026-06-18
spec: specs/2026-06-18_bubble-wrap-staging-damage
---

# Plan: Bubble wrap sizing at issue + staging damage

## Phase 1 — Schema + migration

- [x] `sql/migration_phase38_bubble_wrap_staging_damage.sql`
- [x] Generic SKU `Bubble wrap roll`; migrate warehouse qty from sized SKUs
- [x] `damaged_qty` on `fulfillment_packing_balances`
- [x] `fulfillment_packing_damage_events` table
- [x] Issue line audit columns (`source_stock_id`, `bubble_width_cm`)

## Phase 2 — Backend

- [x] `fulfillment_packing_issue_create` — bubble split: deduct generic, credit sized staging
- [x] `fulfillment_packing_report_damage` — new function + API action
- [x] `fulfillment_packing_balances_list` — include `damaged_qty`, `remaining = issued - consumed - damaged`
- [x] Role: allow **all TL** (`tl`, `csr_tl`) on issue + damage
- [x] `impact.php` on touched `includes/` functions; regenerate memory bank

## Phase 3 — SPA

- [x] `PackingMaterialsStagingPage` — bubble size form in issue modal; damage modal per row
- [x] `PackagingMaterialsPage` — hide sized bubble from warehouse list; show generic bubble
- [x] `routes.ts` + `Sidebar.tsx` — `/fulfillment/packing-materials` for `team_leader`, `csr_tl`
- [x] Update `PackagingInventoryGuideModal` copy
- [x] Motion: `AppModal`, `Button`, success/error banners per `005`

## Phase 4 — Verify

- [x] `tools/verify.ps1`
- [ ] Manual: stock-in 2 generic rolls → issue 1 roll as 22 cm → staging 22 cm +1 → report 0.25 roll damaged → remaining math
- [x] Problem record + `002_Memory_Bank.md`
