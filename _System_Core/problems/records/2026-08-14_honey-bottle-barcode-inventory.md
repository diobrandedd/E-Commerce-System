---
date: 2026-08-14
type: problem-record
status: fixed
area: honey
tags: [honey, barcodes, inventory, pos, parcels]
---

# Problem: Honey stock was aggregate-only; bottles had no unique identity

> Feature rollout — per-bottle barcodes become the inventory source of truth.

## Symptom

Packaging counted bottles as decimals on `honey_packaging_rows`. POS, parcels, and RTS deducted FIFO with no sticker identity. Sales log stored NULL batch numbers.

## Cause

→ [[causes/honey-aggregate-vs-units]]

## Fix

→ [[fixes/honey-bottle-units-mirror-aggregates]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` |
| Migration | `sql/migration_phase54_honey_bottle_barcodes.sql` |
| SPA | Production modals (`HoneyProductionPage` + `HoneyBottleBarcodePanels`); Pack scan page; Bottles tab removed |
| Print | A4 4×17 = 68/page — barcode only, dashed cut guides. 40mm symbol + 5mm quiet zones ≈ 0.4mm bars, the floor for phone-camera scanning; print at 100% scale |
| Codes | Unique 6-char lookup; `honey_bottle_codes_issued` never reuses a value |
| Shell | `honey_bottle_ensure_packaging_shell` — packaging batch without gallon deduct |
| Ship | `includes/online_order_parcels.php` — parse only, no deduct |
| POS | `includes/honey_pos.php`, `app/src/app/pages/pos/POSHoneyPage.tsx` |
| Sales | `includes/honey_sales_report.php` |

## Deploy

- Run `sql/migration_phase54_honey_bottle_barcodes.sql`
- `composer install` (picqer/php-barcode-generator)
- `cd app && npm run build` + hard refresh Apache `app/dist/`
- regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Online order parcels + ship deduct]]
- **Plan:** [[plans/active/2026-08-14_honey-bottle-barcode-inventory_plan]]
- **Spec:** [[specs/2026-08-14_honey-bottle-barcode-inventory]]

## AI rules (optional)

Never increment `honey_packaging_rows.current_*` on barcode backfill or production stock-out — only on request-sourced scan stock-in. Never deduct honey/dept/fulfillment at `online_parcels_ship`.
