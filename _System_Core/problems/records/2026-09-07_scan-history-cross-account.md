---
date: 2026-09-07
type: problem-record
status: fixed
area: inventory-scan-history
tags: [problem, inventory, barcodes]
---

# Problem: Scan history looked empty / single-product for other accounts

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Noli_F (executive) opened **Scan history — finished pouches** and saw 0 for today on one product page. Needed to see stock-in by other accounts (e.g. Leo Bautista on BRC/Cacao) across Paragis, Honey, BRC, and Cacao.

## Cause

→ [[causes/scan-history-product-scoped-no-by-scanner]]

## Fix

→ [[fixes/scan-history-by-scanner-product-tabs]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/dept_pouch_barcodes.php` (`by_scanner`, SKU rows in request scan log) |
| Related | `includes/honey_bottle_barcodes.php` (`packaging_scan_report.by_scanner`) |
| SPA | `app/src/app/components/dept/DeptPouchBarcodePanels.tsx` |
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| Client | `app/src/app/lib/deptPouchBarcodes.ts`, `bottleBarcodes.ts` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh Apache `app/dist/`

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Feedback animations & hover (mandatory)]]
