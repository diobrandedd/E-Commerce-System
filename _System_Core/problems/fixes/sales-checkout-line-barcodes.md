---
date: 2026-08-26
type: fix
area: sales-checkouts
tags: [fix]
---

# Fix: Attach barcodes per checkout line

Load honey codes from `honey_sales_log` (+ linked units) and dept codes from `consumed_ref` POS units; match onto receipt lines by variant/size or `dept_product_key`. SPA shows mono codes under each line; mark `returned` when the unit is no longer `sold`.
