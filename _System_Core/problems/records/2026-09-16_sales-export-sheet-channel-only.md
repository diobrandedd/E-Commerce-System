---
date: 2026-09-16
type: problem-record
status: fixed
area: sales-report
tags: [problem]
---

# Problem: Sales Excel Shipped sheet also showed Walk-in summary

## Symptom

Export “Shipped” tab header compared Walk-in vs Shipped side-by-side; too much noise for a shipped-only sheet (same on Walk-in).

## Cause

→ [[causes/sales-excel-dual-channel-summary]]

## Fix

→ [[fixes/sales-export-sheet-channel-only]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` |
| Related | [[generated/modules/honey_sales_report]] |

## Deploy

- PHP only (no SPA rebuild required for this change)
