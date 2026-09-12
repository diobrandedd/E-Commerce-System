---
date: 2026-09-12
type: problem-record
status: fixed
area: inventory-check
tags: [problem, inventory-check, barcodes]
---

# Problem: Check inventory asked for batch/date on unique stickers

## Symptom

Auditors scanning unique stickers (honey / Paragis / BRC / cacao) on Check inventory were forced into the same “enter batch + bottling date” form as SKU codes, and the camera closed — even though Verify barcode already shows those fields on the sticker.

## Cause

Phase batch-docs required documentation on **every** scan. The SPA treated `unique_honey` / `unique_dept` like SKU (pending docs + close camera). The API also required client-supplied docs before using sticker metadata from `inventory_check_resolve_*`.

## Fix

- Unique scans auto-commit using sticker batch + date (API fills from resolve when omitted); camera stays continuous with “Scan complete” feedback.
- SKU scans still pause for batch, date, and qty.
- Lookup returns `bottling_date` for unique honey/dept for the SPA path.

## Files

| Role | Path |
|------|------|
| API | `includes/inventory_check.php`, `includes/sku_barcodes.php` |
| SPA | `app/src/app/pages/inventory/InventoryCheckPage.tsx`, `app/src/app/lib/skuBarcodes.ts` |
| Tests | `tests/inventory/inventory-check-scan-only.spec.ts` |
| Spec | [[specs/2026-09-11_inventory-check-batch-docs]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
