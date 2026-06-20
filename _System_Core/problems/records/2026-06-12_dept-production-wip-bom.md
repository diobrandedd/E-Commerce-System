---
date: 2026-06-12
type: problem-record
status: fixed
area: inventory
tags: [problem, paragis, production, wip]
---

# Problem: Bag-based produce did not match tea bag / pouch workflow

## Symptom

Prod managers could only stock in/out whole bags; no WIP tea bag count; packaging not deducted on production; custom costing lines could have been confused with inventory. Follow-up: [[records/2026-06-12_pt-make-tea-bags-blocked]] when extra formula packaging (e.g. Yarn) blocked Make tea bags.

## Cause

`dept_raw_produce` converted bags directly to finished units in one step. No `dept_wip_rows` or formula-driven operational deduct.

## Fix

→ [[fixes/dept-production-wip-bom]] · [[specs/2026-06-12_paragis-production-wip]]

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase26_dept_wip.sql` |
| Backend | `includes/dept_production.php`, `api/v1/honey.php` |
| SPA | `RawDeptMaterialPage.tsx`, `deptRawInventory.ts` |

## Deploy

- Run `migration_phase26_dept_wip.sql`
- `cd app && npm run build`
