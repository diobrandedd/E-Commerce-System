---
date: 2026-08-29
type: problem-record
status: fixed
area: honey-bottle-barcode-inventory
tags: [problem, honey, sku, barcode, packaging, reporting]
---

# Problem: Today filter omitted honey SKU stock-ins

## Symptom

The Honey Packaging page did not show a current SKU quantity stock-in when Today was selected, although the page still showed historical rows when All dates was selected.

## Cause

→ [[causes/honey-packaging-today-sku-events-hidden]]

## Fix

→ [[fixes/honey-packaging-today-sku-events]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` |
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| Related | [[generated/modules/honey_bottle_barcodes]] |

## Deploy

- Run the phase 70 SKU history migration if it has not been applied.
- Run PHP syntax checks and `cd app && npm run build`.
- Hard-refresh the SPA after deployment.
- Regenerate memory bank.

## Links

- **Fragile edges:** [[003_Fragile_Edges#SKU barcodes (phase 69)]]
- **Patterns:** [[005_SPA_UX_Patterns#Honey / inventory UX]]
- **Plan:** [[plans/active/2026-08-14_honey-bottle-barcode-inventory_plan]]
- **Spec:** [[specs/2026-08-26_sku-barcode-inventory]]

## AI rules

When reporting gross packaging stock-in, include both unique barcode events and quantity-based SKU stock events; do not rely only on `honey_bottle_barcodes`.
