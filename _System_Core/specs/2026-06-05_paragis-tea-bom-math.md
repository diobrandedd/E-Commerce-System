---
status: reference
created: 2026-06-05
area: dept-paragis-costing
tags: [paragis, pt, bom, costing, math]
---

# Paragis Tea — production & costing math

> Code: `PT_GRAMS_PER_BAG = 1000` (1 kg), `PT_GRAMS_PER_TEA_BAG = 2`, `PT_TEA_BAGS_PER_POUCH = 24`.

## Units

| Stage | Unit | Meaning |
|-------|------|---------|
| Supplier | **bag** | Raw leaf **1 kg** (1,000 g) |
| Production | **tea bag** | One sachet = **2 g** leaf |
| Sale / inventory | **pouch** | Retail pack = **24 tea bags** |

## Yield

```
1,000 g ÷ 2 g = 500 tea bags per bag
500 ÷ 24 = 20.833… pouches per bag
```

Stock-in: `pouches_added = bags × (500 ÷ 24)`

## Costing

```
48 g leaf per pouch ÷ 1,000 g per bag = 24 ÷ 500 = 0.048 bag per pouch
```

Packaging line: `1` pouch packaging per sell unit.

## Example

₱400/bag leaf → ₱400 ÷ 20.83 ≈ **₱19.20/pouch** leaf (+ packaging on Costing).
