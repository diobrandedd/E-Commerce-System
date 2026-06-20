---
date: 2026-06-12
type: fix
area: dept-paragis-production
tags: [paragis, pt, wip, production, ux]
---

# Fix: Paragis — use raw bags instead of counting tea bags

## Problem

**Make tea bags** required staff to enter an exact count of individual 2 g tea bags. Large runs (e.g. 14 bags → ~7,000 tea bags) are impractical to count.

## Fix

Primary action is **Use raw bags** — staff enter **1 kg supplier bags opened**; system derives WIP tea bags (`bags × 500`) and deducts raw leaf by bag qty.

| Action | Input | WIP |
|--------|-------|-----|
| `use_raw_bags` (primary) | Bags opened | +derived tea bags |
| `make_tea_bags` (advanced) | Exact tea bag count | +qty |
| `pouch` | Pouch count | −24×pouches |

## API

- `dept_pt_use_raw_bags` — POST `{ qty: bags, note? }`
- Preview: `dept_production_preview` with `action=use_raw_bags`

## Files

- `includes/dept_production.php` — `dept_pt_use_raw_bags()`, preview `derived_tea_bags`
- `api/v1/honey.php`
- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx` — `UseRawBagsModal`
- `app/src/app/lib/deptRawInventory.ts`
- [[specs/2026-06-12_paragis-production-wip]]

## UX / guides

Workflow docs live in **Guide** modal only: [[fixes/dept-raw-material-guides]] · `ParagisProductionGuideModal.tsx`.

## Links

[[fixes/dept-production-wip-bom]] · [[fixes/dept-raw-material-guides]] · [[specs/2026-06-05_paragis-tea-bom-math]] · [[records/2026-06-12_dept-raw-ux-session]]
