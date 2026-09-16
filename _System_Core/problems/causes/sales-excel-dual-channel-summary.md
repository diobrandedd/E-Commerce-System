---
date: 2026-09-16
type: cause
area: sales-report
tags: [cause]
---

# Cause: Excel summary duplicated both channels on each sheet

`honey_sales_export_write_summary_block()` always wrote Walk-in (cols A–B) and Shipped (cols D–E) on **both** workbook sheets, so the Shipped tab still showed walk-in totals and breakdowns.
