---
date: 2026-06-11
type: problem-record
status: fixed
area: costing
tags: [problem, costing, formula]
---

# Problem: Formula lines required a stock — no labor / shipping overhead

> Ops need flat costs (labor, shipping) on product BOM without creating inventory stocks.

## Symptom

Add formula line only allowed picking dept/packaging stocks. Overhead could not be named and priced directly.

## Cause

Formula schema required `stock_id` FK — overhead (labor, shipping) had no inventory SKU.

## Fix

→ [[fixes/costing-page-layout-and-guide#Add formula line]]

- `migration_phase24_formula_custom_lines.sql` — `product_formula.stock_id` nullable + `line_label`
- API `formula_add` accepts `is_custom`, `line_label`, `unit_price`, `qty_per_unit`
- SPA Add formula modal: **From stock** | **Custom cost** tabs
- Current cost: `CASE WHEN stock_id IS NULL THEN price_at_creation ELSE s.current_price END`

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase24_formula_custom_lines.sql` |
| API | `api/v1/costing.php`, `api/v1/_costing_helpers.php` |
| UI | `CostingPage.tsx`, `CostingGuideModal.tsx` |

## Deploy

Run `migration_phase24_formula_custom_lines.sql` on prod.

## Links

- [[problems/records/2026-06-11_costing-session-rollup]]
- [[fixes/costing-page-layout-and-guide]]
