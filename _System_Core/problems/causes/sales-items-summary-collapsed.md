---
date: 2026-09-16
type: cause
status: documented
area: sales
tags: [cause]
---

# Cause: Multi-line checkout summary intentionally collapsed

`itemSummary` / `honey_sales_export_checkout_items_summary` returned a count string when `items.length > 1`, so Export Excel copied that placeholder into column E.
