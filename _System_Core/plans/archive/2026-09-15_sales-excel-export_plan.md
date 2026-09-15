---
title: Sales Excel export (2 sheets)
status: shipped
approved: true
created: 2026-09-15
area: sales
---

# Implementation Plan: Sales Excel export

> Spec: chat request 2026-09-15 · Related: [[specs/2026-09-10_sales-view-amounts]] · [[003_Fragile_Edges]]

## Goal

Export Sales page filters to a two-sheet `.xlsx`: **Shipped** (pack-scan, RTS resellable column) and **Walk-in** (POS checkouts).

## Locked decisions

- Button above Recent walk-in sales table
- Filters: period, channel, product, sale_type, receipt
- Always both sheets; channel empties the non-matching sheet
- Cap 5,000 rows/sheet
- Strip Total when lacking `sales.view_amounts`
- Server PhpSpreadsheet; action `honey_sold_export`

## Phases

1. [x] Backend fetch + xlsx builder in `honey_sales_report.php`
2. [x] API `honey_sold_export` + SPA Export button / `apiDownload`
3. [ ] Verify + memory bank
