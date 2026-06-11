---
status: reference
created: 2026-06-05
updated: 2026-06-11
area: dept-jar-cacao-costing
tags: [jar_cacao, cacao, bom, costing, math]
---

# Jar Cacao — production & costing math

> Code: `JAR_CACAO_GRAMS_PER_BAG = 1000` (1 kg supplier bag), `JAR_CACAO_GRAMS_PER_JAR = 220`, `JAR_CACAO_TABLETS_PER_JAR = 24`.

## Units

| Stage | Unit | Meaning |
|-------|------|---------|
| Supplier | **bag** | Raw cacao **1 kg** (1,000 g) |
| Inside jar | **24 tablets** | Descriptive only — not a separate costing SKU |
| Sale / inventory | **pcs** (jar) | **220 g** cacao content per jar |

## Yield

```
1,000 g ÷ 220 g = 4.545454… jars per bag
```

Stock-in: `jars_added = bags × (1000 ÷ 220)`

## Costing

```
220 g per jar ÷ 1,000 g per bag = 0.22 bag per jar
```

- `Jar Cacao — raw`: **0.22** bag per jar (₱/bag from Supplier Master)
- `Jar Cacao jar packaging`: **1.0** per jar (WAC from Packaging Materials stock-in)

## Example

₱500/bag → raw line ≈ ₱500 × 0.22 = **₱110/jar** (+ packaging on Costing).

## Links

- [[specs/2026-06-05_paragis-tea-bom-math]] (same grams→fraction pattern as PT)
- [[problems/records/2026-06-11_jar-cacao-220g-bom]]
