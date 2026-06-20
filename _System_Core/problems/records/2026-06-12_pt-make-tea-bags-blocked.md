---
date: 2026-06-12
type: problem-record
status: fixed
area: inventory
tags: [problem, paragis, production, costing]
---

# Problem: Make tea bags blocked after raw stock-in

## Symptom

Prod manager stocked in 20 raw bags on Dried Paragis but **Make tea bags** failed (or page showed no activity). User expected stock-in to create tea bags automatically.

## Cause

→ [[causes/pt-stock-in-vs-make-tea-bags]]

1. **UX:** Stock-in only credits sealed bags; tea bags require a separate **Make tea bags** action.
2. **Backend:** Product Costing formula included **Paragis Tea Bag Yarn** (packaging) but `dept_production_pt_stage_for_stock()` only recognized three exact stock names — unknown formula lines blocked the whole transaction.

## Fix

→ [[fixes/dept-production-wip-bom#Gotchas]]

- Map any **packaging** stock except `PT pouch packaging` to **make_tea_bags** stage (sachet, yarn, etc.).
- SPA: “How Paragis production works” panel on `RawDeptMaterialPage`.

## Files

| Role | Path |
|------|------|
| Backend | `includes/dept_production.php` |
| SPA | `app/src/app/pages/inventory/RawDeptMaterialPage.tsx` |

## Deploy

- `migration_phase26_dept_wip.sql` on prod if not run
- `cd app && npm run build`

## Links

- Parent: [[records/2026-06-12_dept-production-wip-bom]]
- Spec: [[specs/2026-06-12_paragis-production-wip]]
