---
date: 2026-08-29
type: problem-record
status: fixed
area: honey-bottle-barcode-inventory
tags: [problem, honey, sku, barcode, packaging, reporting]
---

# Problem: Packaging details did not show SKU operator and quantity

## Symptom

After a honey SKU stock-in, the Packaging row could show a quantity in the report but opening its detail modal displayed “No stickers” and did not show who entered the quantity.

## Cause

→ [[causes/honey-packaging-scan-log-omits-sku-quantities]]

## Fix

→ [[fixes/honey-packaging-scan-log-quantities]]

## Files

| Role | Path / note |
|------|-------------|
| Shared report/log | `includes/honey_bottle_barcodes.php` |
| API client types | `app/src/app/lib/bottleBarcodes.ts` |
| Detail modal | `app/src/app/components/honey/PackagingScanLogModal.tsx` |
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

Packaging scan reporting must distinguish entry count from bottle quantity and include both unique sticker and aggregated SKU events.
