---
date: 2026-08-29
type: problem-record
status: fixed
area: honey-bottle-barcode-inventory
tags: [problem, honey, barcode, packaging, sku]
---

# Problem: Honey SKU stock-in forced an existing batch selection

## Symptom

The bottle scan modal showed “Select batch…” and blocked SKU stock-in unless an existing packaging row was selected. Operators need to type the batch number directly; supplier-code existence checks are not part of this workflow.

## Cause

→ [[causes/honey-sku-stock-in-batch-dropdown]]

## Fix

→ [[fixes/honey-sku-custom-batch-stock-in]]

## Files

| Role | Path / note |
|------|-------------|
| API | `api/v1/bottle_barcodes.php` |
| Shared scanner client | `app/src/app/lib/bottleBarcodes.ts` |
| SPA scanner | `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` |
| Related | [[generated/modules/honey_bottle_barcodes]] |

## Deploy

- Run PHP syntax checks and `cd app && npm run build`.
- Hard-refresh the SPA after deployment.
- Regenerate memory bank.

## Links

- **Fragile edges:** [[003_Fragile_Edges#Honey packaging batch_no uniqueness]]
- **Patterns:** [[005_SPA_UX_Patterns#Honey / inventory UX]]
- **Plan:** [[plans/active/2026-08-14_honey-bottle-barcode-inventory_plan]]
- **Spec:** [[specs/2026-08-14_honey-bottle-barcode-inventory]]

## AI rules

Keep supplier lookup out of custom SKU stock-in; the typed batch number is an operator-provided label, while duplicate batch+variant rows are reused internally.
