---
date: 2026-09-25
type: problem-record
status: fixed
area: inventory-leak-damage
tags: [problem, feature, sku-barcode]
---

# Problem: Leak / damage scan rejected SKU barcodes

## Symptom

Staff could not log a damaged/leaked bottle stocked only via permanent SKU (no unique sticker). Scan returned Unknown barcode.

## Cause

`finished_leak_damage_scan` only resolved unique honey/dept stickers. SKU catalog (`product_sku_barcodes`) was never consulted. Log table required `unit_id` / `barcode_id`.

## Fix

- Phase 87: nullable unit/barcode ids, `qty`, `sku_id` on `finished_unit_leak_damage_log`.
- Scan path: sticker first → SKU; SKU without qty returns `requires_qty`; with qty deducts packaging/dept stock (no sales log) and inserts log.
- SPA: pending SKU qty confirm (default 1); history shows Qty.

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase87_leak_damage_sku.sql` |
| PHP | `includes/finished_leak_damage.php` |
| API | `api/v1/finished_leak_damage.php` |
| SPA | `app/src/app/pages/inventory/FinishedLeakDamagePage.tsx` |
| Client | `app/src/app/lib/finishedLeakDamage.ts` |

## Deploy

- Run phase 87 on each env
- `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- Related: [[problems/records/2026-08-18_leak-damage-unified-pos-sales-dates]] · [[problems/fixes/sku-barcode-permanent-catalog]]
