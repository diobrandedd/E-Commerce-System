---
date: 2026-08-26
type: fix
tags: [fix]
---

# Fix: Reverse receipt line + heal historical returns

On sold restock confirm: `pos_sale_reverse_one_unit` then recalc totals. Sales checkout load runs idempotent `pos_sale_heal_returned_units` for honey. Returned barcodes listed under a separate “Returned” block; paid lines keep active codes only.
