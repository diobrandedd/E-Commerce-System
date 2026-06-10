---
status: reference
created: 2026-06-05
area: dept-brc-costing
tags: [brc, black-rice, bom, costing, math]
---

# Black Rice Coffee — production & costing math

> Canonical business rules for BRC BOM. Code: `includes/dept_products.php` (`BRC_GRAMS_PER_BAG`, `BRC_GRAMS_PER_POUCH`). Implementation: [[problems/records/2026-06-05_brc-supplier-bom-costing]].

## Units

| Stage | Unit | Meaning |
|-------|------|---------|
| Supplier | **bag** | Raw product **20 kg** (20,000 g) |
| Sale / inventory | **pouch** | Retail pack ≈ **100 g** |

Stock-in is in **bags**; on-hand, POS, and parcel deduct are in **pouches**.

## Yield — pouches per bag

```
20,000 g ÷ 100 g per pouch = 200 pouches per bag
```

**Stock-in:**

```
pouches_added = bags_received × 200
```

Code: `dept_brc_pouches_per_bag()`.

## Costing — bags per pouch

```
100 g ÷ 20,000 g = 0.005 bag per pouch
```

**Formula** on `Black Rice Coffee — raw` (unit = bag): `qty_per_unit = 0.005`  
**Packaging** on `BRC pouch packaging`: `qty_per_unit = 1` (manual ₱/pouch on Costing → Stocks).

Code: `dept_brc_bags_per_pouch()`.

## Cost example

- Supplier Master: **₱2,000 / bag**
- Packaging: **₱8 / pouch**

Leaf per pouch: ₱2,000 ÷ 200 = **₱10** (same as ₱2,000 × 0.005)  
Baseline: **₱18 / pouch** before selling price.

## Links

- [[specs/2026-06-05_paragis-tea-bom-math]] (PT parallel)
- [[problems/records/2026-06-05_brc-supplier-bom-costing]]
