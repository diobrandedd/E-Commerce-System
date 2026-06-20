---
date: 2026-06-11
type: problem-record
status: fixed
area: costing
tags: [problem, jar_cacao, bom]
---

# Problem: Jar Cacao BOM used placeholder 1 kg bag = 1 jar

> Real yield: 1 kg bag → ~4.55 sellable jars @ 220 g (24 tablets inside).

## Symptom

Costing and stock-in treated one supplier bag as one jar (`JAR_CACAO_GRAMS_PER_JAR = 1000`, formula qty 1.0 bag/jar). Unit economics were wrong vs production.

## Cause

Dept products v1 shipped with placeholder 1:1 bag-to-jar math before ops confirmed 220 g per jar.

## Fix

- `JAR_CACAO_GRAMS_PER_JAR = 220`, `JAR_CACAO_TABLETS_PER_JAR = 24` in `dept_products.php` + `deptProducts.ts`
- `migration_phase22_jar_cacao_220g_bom.sql` — formula raw qty **0.22**; recalc `baseline_cost`
- Updated [[specs/2026-06-05_jar-cacao-bom-math]]

## Files

| Role | Path |
|------|------|
| Constants | `includes/dept_products.php`, `app/src/app/lib/deptProducts.ts` |
| Migration | `sql/migration_phase22_jar_cacao_220g_bom.sql` |
| Spec | `_System_Core/specs/2026-06-05_jar-cacao-bom-math.md` |

## Deploy

Run `migration_phase22_jar_cacao_220g_bom.sql` on prod; `cd app && npm run build`

## Links

- [[problems/records/2026-06-11_costing-session-rollup]]
- [[003_Fragile_Edges#Dept catalog products (PT / BRC / Cacao)]]
- [[specs/2026-06-05_jar-cacao-bom-math]]
