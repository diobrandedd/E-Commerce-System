---
date: 2026-08-26
type: problem-record
status: fixed
area: inventory-barcodes
tags: [sku, barcodes, spa, pos, packing]
---

# Problem: SPA still printed unique batches / scanned without qty

> SKU barcode Phase 2 frontend — Settings catalog, print_url, scan+qty UIs.

## Symptom

Generate/print opened unique-batch sheets; POS / inventory check / pack / RTS stock-in treated every scan as one unique unit with no qty.

## Cause

Frontend clients and pages still assumed per-unit generate + one scan = one unit, before permanent SKU catalog.

## Fix

Settings Barcode Settings (`skuBarcodesApi`); honey/dept generate uses `print_url`; POS cart SKU lines with editable qty; inventory check / pack / RTS lookup-first + qty modal; unique leftovers unchanged.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `app/src/app/pages/exec/SettingsPage.tsx` |
| Related | `app/src/app/lib/skuBarcodes.ts`, `posUnified.ts`, `bottleBarcodes.ts`, `inventoryCheck.ts`, `rtsApi.ts` |
| Related | `HoneyBottleBarcodePanels.tsx`, `DeptPouchBarcodePanels.tsx`, `POSUnifiedPage.tsx`, `InventoryCheckPage.tsx`, `HoneyPackScanPage.tsx`, `RTSPage.tsx` |
| Spec | [[specs/2026-08-26_sku-barcode-inventory]] |
