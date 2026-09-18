---
date: 2026-09-18
type: fix
area: sales
tags: [fix]
---

# Fix: Chunked multi-sheet Sales Excel export

## Behavior

1. Export builds **Summary** (period totals + size breakdowns) first.
2. Shipped detail is split into **Shipped** / **Shipped 2…N** at **5,000 rows per sheet**.
3. Walk-in detail similarly chunked when needed.
4. Absolute safety cap **100,000** detail rows per channel (still asks to narrow period if exceeded).
5. Same filters as before (period, channel, product, sale_type, receipt).

## Why

Monthly shipped volume can exceed 5,000 rows; a single-sheet cap blocked real month exports.
