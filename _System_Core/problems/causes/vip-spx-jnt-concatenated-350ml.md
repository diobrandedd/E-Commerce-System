---
date: 2026-06-19
type: cause
area: csr-order-remarks
tags: [cause, jnt, 350ml]
---

# Cause: J&T Item Name concatenates 3L + 350ml without separator

## What happened

J&T **Item Name** (column Y) sometimes packs main honey + bulk ml in one string with **no** `;`, `+`, or comma:

`3L HONEY(ALL DARK)1 PC 350ML(DARK)`

`splitItemSegments()` only split on `;` / `+` / comma — entire string became **one segment**. `detectHoneySize()` saw `3L HONEY` first and never parsed the trailing `1 PC 350ML(DARK)`.

## Symptom

Wealthyneil C. Yap (J&T row 22): export **`1 x 3L Honey (DDD)`** only — missing **`+ 1× 350ml (D)`**.

## Rule for agents

After primary splits, run **`splitJntConcatenatedBulkMl()`** when segment matches:

`^(honey block)(\d+ PC (250|350)ML…)$`

Also parse **`350ML(DARK)`** — word in parens, not just `(D)`.

## Related

- [[fixes/vip-spx-order-remarks-parser-guards]]
- [[records/2026-06-19_vip-spx-parser-pitfalls]]
