---
date: 2026-11-24
type: problem-record
status: fixed
area: honey-packaging
tags: [problem]
---

# Problem: Wrong packaging stock-in could not be undone from the report

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Coworkers sometimes stocked the wrong bottles via Production → Scan bottles into packaging. Packaging → “Stocked into inventory” showed the mistaken person/size lines, but the report was read-only. Products → Honey Edit could fix live on-hand without reversing scan history, so the report stayed wrong.

## Cause

→ [[causes/packaging-report-read-only-no-line-undo]]

## Fix

→ [[fixes/packaging-stock-in-line-undo]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` (`honey_bottle_packaging_scan_line_undo`) |
| Gallons | `includes/honey_inventory.php` (`honey_production_credit_gallons_for_variant`) |
| API | `api/v1/bottle_barcodes.php` (`packaging_scan_line_undo`) |
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| Client | `app/src/app/lib/bottleBarcodes.ts` |
| Spec | [[specs/2026-11-24_packaging-stock-in-line-undo]] |

## Follow-up (2026-11-24 local test)

Undo SELECT used `c.request_id` on `honey_bottle_barcodes`; column is on `honey_barcode_batches` → `b.request_id`. Fixed after local Confirm undo error `Unknown column 'c.request_id'`.

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Honey Packaging page (2026-09-17 report)]]
- **Theory:** [[010_Product_Theory]]
