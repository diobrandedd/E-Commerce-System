---
date: 2026-09-18
type: problem-record
status: fixed
area: sales
tags: [problem]
---

# Problem: Today Sales export labeled as whole-month Summary; detail sheets lacked mini breakdown

## Symptom

Export with Period **Today** produced sheet `Sep Summary` / “monthly summary / Calendar month”, and shp/wlk sheets were raw tables only (no by size / variant / variant×size header).

## Cause

Weekly export always wrote **per-calendar-month** summary titles even when the filter was a single day; detail writers never called `honey_sales_export_write_summary_block`.

## Fix

→ [[fixes/sales-excel-weekly-named-sheets]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` |

## Deploy

- PHP only
