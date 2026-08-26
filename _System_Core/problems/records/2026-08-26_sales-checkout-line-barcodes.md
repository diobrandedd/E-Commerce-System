---
date: 2026-08-26
type: problem-record
status: fixed
area: sales-checkouts
tags: [problem]
---

# Problem: Walk-in sales expand showed product lines without barcodes

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Sales → Recent walk-in sales expanded a receipt to “Light · 1L × 1” rows with prices only — no sticker codes, so ops could not tell which barcodes were on that checkout.

## Cause

→ [[causes/sales-checkout-no-barcodes]]

## Fix

→ [[fixes/sales-checkout-line-barcodes]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_sales_report.php` |
| Related | [[generated/modules/honey_sales_report]] |
| SPA | `app/src/app/pages/exec/SalesPage.tsx` |

## Deploy

- PHP + SPA build + hard refresh

## Links

- Related: [[problems/records/2026-08-26_pack-cancel-restock-pos-sold]]
