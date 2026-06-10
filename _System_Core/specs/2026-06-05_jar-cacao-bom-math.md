---
status: reference
created: 2026-06-05
area: dept-jar-cacao-costing
tags: [jar_cacao, cacao, bom, costing, math]
---

# Jar Cacao — production & costing math

> Code: `JAR_CACAO_GRAMS_PER_BAG = 1000` (1 kg supplier bag), `JAR_CACAO_GRAMS_PER_JAR = 1000` (one sellable jar per bag).

## Units

| Stage | Unit | Meaning |
|-------|------|---------|
| Supplier | **bag** | Raw cacao **1 kg** |
| Sale / inventory | **pcs** (jar) | **1 kg** finished jar per supplier bag |

## Yield

```
1 bag (1 kg) → 1 jar on inventory
```

Stock-in: `jars_added = bags_received` (when 1 kg bag = 1 kg jar).

## Costing

- `Jar Cacao — raw`: **1.0** bag per jar
- `Jar Cacao jar packaging`: **1.0** per jar (manual ₱ on Costing → Stocks)

If jar net weight is **not** 1 kg, update `JAR_CACAO_GRAMS_PER_JAR` in `dept_products.php` / `deptProducts.ts`.

## Links

- [[problems/records/2026-06-05_brc-supplier-bom-costing]] (BRC remains 20 kg bag)
- [[specs/2026-06-05_paragis-tea-bom-math]]
