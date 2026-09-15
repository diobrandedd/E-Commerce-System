---
date: 2026-09-15
type: cause
area: dept-raw-inventory
tags: [cause]
---

# Cause: Catalog `stock_in_unit` was `bag` for leaf products

Paragis, BRC, and Jar Cacao used `stock_in_unit = bag` and UI copy assumed sealed 1 kg bags. Internally `grams_per_bag` defaulted to 1000, so qty was already kg-equivalent, but ops were told to enter bag counts.
