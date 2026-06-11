---
date: 2026-06-11
type: problem-record
status: fixed
area: costing
tags: [rollup, jar_cacao, brc, costing, bom]
---

# Rollup: Product Costing session (2026-06-11)

> Chat-driven batch: dept BOM fixes + costing UI + custom formula lines. Use this as the session index.

## Shipped

### 1. Jar Cacao BOM (220 g / jar)

- 1 kg supplier bag → **~4.55** sellable jars @ 220 g (24 tablets inside, descriptive).
- Formula raw: **0.22** bag/jar.
- [[problems/records/2026-06-11_jar-cacao-220g-bom]] · [[specs/2026-06-05_jar-cacao-bom-math]]
- Migration: `sql/migration_phase22_jar_cacao_220g_bom.sql` (use `LIKE 'Jar Cacao%raw'` for em-dash safety).

### 2. Black Rice Coffee BOM (1 kg bag)

- Was 20 kg → 200 pouches; corrected to **1 kg → 10** pouches @ 100 g.
- Formula raw: **0.1** bag/pouch (was 0.005).
- [[problems/records/2026-06-11_brc-1kg-bom]] · [[specs/2026-06-05_brc-bom-math]]
- Migration: `sql/migration_phase23_brc_1kg_bom.sql`

### 3. Product Costing UI

- Grid-aligned rows, scrollable product list, Guide modal.
- Removed honey setup + stock prices panels.
- [[problems/records/2026-06-11_costing-page-ux-refactor]] · [[fixes/costing-page-layout-and-guide]]

### 4. Custom formula lines (labor, shipping, …)

- `product_formula.line_label` + nullable `stock_id`; fixed ₱ per sell unit.
- Add formula → **Custom cost** tab.
- [[problems/records/2026-06-11_costing-custom-formula-lines]]
- Migration: `sql/migration_phase24_formula_custom_lines.sql`

## Constants (keep PHP + TS in sync)

| Constant | PT | BRC | Jar Cacao |
|----------|----|-----|-----------|
| Grams per supplier bag | 1000 | 1000 | 1000 |
| Sell unit content | 48 g/pouch | 100 g/pouch | 220 g/jar |
| Formula raw qty | 0.048 | 0.1 | 0.22 |

`includes/dept_products.php` · `app/src/app/lib/deptProducts.ts`

## Prod deploy (run in order if not applied)

```text
migration_phase22_jar_cacao_220g_bom.sql
migration_phase23_brc_1kg_bom.sql
migration_phase24_formula_custom_lines.sql
cd app && npm run build
```

## Manual E2E

- [ ] Jar Cacao: ₱500/bag → raw line ≈ ₱110/jar (500 × 0.22); stock-in 1 bag → ~4.55 jars
- [ ] BRC: ₱100/bag → raw ≈ ₱10/pouch; stock-in 1 bag → 10 pouches
- [ ] PT formulas unchanged (0.048)
- [ ] Costing at 100% zoom — columns align; Guide opens
- [ ] Custom line: Labor ₱15 × 1 → prod cost +₱15

## Links

- [[003_Fragile_Edges#Dept catalog products (PT / BRC / Cacao)]]
- [[003_Fragile_Edges#Product Costing (SPA)]]
- [[005_SPA_UX_Patterns#Product Costing page]]
