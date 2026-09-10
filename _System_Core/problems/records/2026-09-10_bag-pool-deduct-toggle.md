---
date: 2026-09-10
type: problem-record
status: fixed
area: inventory-settings
tags: [bag-pool, settings, pt, brc, jar-cacao]
---

# Problem: No Settings control to disable bag-pool deduct on finished stock-in

## Symptom

Paragis / BRC / Cacao finished barcode stock-in always deducted raw bags. Ops needed an executive switch to credit finished goods without consuming the bag pool.

## Cause

→ [[causes/bag-pool-deduct-always-on]]

## Fix

→ [[fixes/bag-pool-deduct-settings-toggle]]

## Files

| Role | Path / note |
|------|-------------|
| Settings | `includes/dept_pt_settings.php`, `dept_brc_settings.php`, `dept_jar_cacao_settings.php` |
| Gate | `includes/dept_pouch_barcodes.php`, `sku_barcodes.php` |
| SPA | `SettingsPage.tsx`, `DeptBagPoolStrip.tsx` |
| Spec | [[specs/2026-09-10_bag-pool-deduct-toggle]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank
