---
date: 2026-09-04
type: problem-record
status: fixed
area: barcodes
tags: [problem, sku, pack-scan, stock-in, inventory-check]
---

# Problem: SKU barcodes could not be scanned continuously

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Pack scan and scan-to-inventory required one SKU scan then typing quantity in a modal. Operators could not keep scanning identical SKU stickers to accumulate count, even though unique stickers scanned continuously.

## Cause

→ [[causes/sku-scan-modal-only-qty]]

## Fix

→ [[fixes/sku-continuous-scan-plus-bulk]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` (`honey_bottle_pack_session_scan_sku`) |
| Primary | `includes/inventory_check.php` (`inventory_check_scan`) |
| UI | `app/src/app/pages/deliveries/HoneyPackScanPage.tsx` |
| UI | `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` |
| UI | `app/src/app/pages/inventory/InventoryCheckPage.tsx` |
| Spec | [[specs/2026-08-26_sku-barcode-inventory]] |

## Related

- [[problems/records/2026-09-04_pack-confirm-mixed-sku-unique]]
- [[specs/2026-08-26_sku-barcode-inventory]]
