---
date: 2026-09-04
type: problem-record
status: fixed
area: honey-production
tags: [problem, honey, stock-in, settings]
---

# Problem: Production bottle scan-in without gallon deduct

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Production page promised “scan stock-in”, but bottling scan lived only on Packaging with a custom batch. Stocking SKU bottles did not deduct supplier gallons from the production lot, so packaging bottle counts and production gallons drifted apart.

## Cause

→ [[causes/packaging-scan-bypassed-production-gallons]]

## Fix

→ [[fixes/production-scan-stock-in-with-gallon-setting]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_inventory.php` (`honey_production_bottle_stock_in`) |
| Volume | `includes/honey_volume.php`, `includes/app_settings.php` |
| Migration | `sql/migration_phase75_app_settings.sql` |
| API | `api/v1/bottle_barcodes.php`, `api/v1/settings.php` |
| SPA | `HoneyProductionScanStockInPanel`, `HoneyProductionPage.tsx`, `SettingsPage.tsx` |

## Deploy

- Run `migration_phase75_app_settings.sql`
- `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Honey module circular dependency]]
- Spec: [[specs/2026-08-26_sku-barcode-inventory]]
