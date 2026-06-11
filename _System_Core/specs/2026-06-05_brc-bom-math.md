---
status: reference
created: 2026-06-05
updated: 2026-06-11
area: dept-brc-costing
tags: [brc, black-rice, bom, costing, math]
---

# Black Rice Coffee — production & costing math

> Code: `BRC_GRAMS_PER_BAG = 1000` (1 kg supplier bag), `BRC_GRAMS_PER_POUCH = 100`.

## Units

| Stage | Unit | Meaning |
|-------|------|---------|
| Supplier | **bag** | Raw product **1 kg** (1,000 g) |
| Sale / inventory | **pouch** | Retail pack ≈ **100 g** |

Stock-in is in **bags**; on-hand, POS, and parcel deduct are in **pouches**.

## Yield — pouches per bag

```
1,000 g ÷ 100 g per pouch = 10 pouches per bag
```

**Stock-in:**

```
pouches_added = bags_received × 10
```

Code: `dept_brc_pouches_per_bag()`.

## Costing — bags per pouch

```
100 g ÷ 1,000 g = 0.1 bag per pouch
```

**Formula** on `Black Rice Coffee — raw` (unit = bag): `qty_per_unit = 0.1`  
**Packaging** on `BRC pouch packaging`: `qty_per_unit = 1` (WAC from Packaging Materials).

Code: `dept_brc_bags_per_pouch()`.

## Cost example

- Supplier Master: **₱100 / bag**
- Packaging: **₱8 / pouch**

Leaf per pouch: ₱100 ÷ 10 = **₱10** (same as ₱100 × 0.1)  
Baseline: **₱18 / pouch** before selling price.

## Links

- [[specs/2026-06-05_paragis-tea-bom-math]] (PT parallel)
- [[problems/records/2026-06-11_brc-1kg-bom]]
