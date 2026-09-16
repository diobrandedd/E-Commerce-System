---
date: 2026-09-16
type: fix
area: sales-report
tags: [fix]
---

# Fix: Include SKU pack qty in dept Sales shipped

In `honey_sales_dept_breakdown()` and `honey_sales_fetch_shipped_export()`, add confirmed pack-session dept lines where `sku_id > 0` and `unit_id` is null — `SUM(qty)` / one export row per line — on top of unique `dept_pouch_units`. Date filter uses `confirmed_at` (same as Pack History).

Covers **pt / brc / jar_cacao / honey_comb**. Honey unchanged (already via `honey_sales_log`).
