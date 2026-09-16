---
date: 2026-09-16
type: problem-record
status: fixed
area: sales
tags: [problem]
---

# Problem: Sales Items showed “N line items” in UI and Excel

## Symptom

Multi-line walk-in checkouts displayed and exported as “2 line items” instead of product names and qtys.

## Cause

→ [[causes/sales-items-summary-collapsed]]

## Fix

→ [[fixes/sales-items-summary-list-all]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` (`honey_sales_export_checkout_items_summary`) |
| SPA | `app/src/app/pages/exec/SalesPage.tsx` (`itemSummary`) |

## Deploy

- PHP + SPA build + hard refresh
