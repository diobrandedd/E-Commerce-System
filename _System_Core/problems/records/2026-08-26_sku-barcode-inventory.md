---
date: 2026-08-26
area: inventory
tags: [barcodes, sku, inventory, pos, packing]
---

# SKU barcodes (one code per product)

## Problem

Retail buyers cannot store unique per-bottle stickers as their POS product barcode. Unique generation was expensive and incompatible with SKU pricing systems.

Cause: [[causes/unique-barcode-vs-retail-sku]]
Fix: [[fixes/sku-barcode-permanent-catalog]]

## Symptom

Need one permanent barcode per product (e.g. 250ml Light), print N copies, scan → enter qty for inventory/pack/POS.

## Cause

Phase 54+ minted unique 6-character codes per unit; every scan keyed off `barcode_id` / `unit_id`.

## Fix

- `product_sku_barcodes` catalog (Barcode Settings)
- Print identical Code128 copies; honey/dept/RTS generate no longer mint unique codes for new labels
- Shared lookup: SKU then leftover unique
- Stock-in / pack / POS / inventory check / RTS stock-in accept qty for SKU
- Leftover unique stickers keep working until consumed

## Files

- `sql/migration_phase69_sku_barcodes.sql`
- `includes/sku_barcodes.php`
- `api/v1/sku_barcodes.php`
- `includes/pos_unified.php`, `inventory_check.php`, `barcode_verify.php`, `honey_bottle_barcodes.php`, `rts_barcode_labels.php`
- `api/v1/bottle_barcodes.php`, `dept_pouch_barcodes.php`, `rts.php`, `inventory_check.php`
- SPA: Settings, scan panels, POS, pack, inventory check, RTS
- Spec: [[specs/2026-08-26_sku-barcode-inventory]]
