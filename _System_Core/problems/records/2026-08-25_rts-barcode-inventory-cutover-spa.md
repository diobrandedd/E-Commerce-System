---
date: 2026-08-25
type: problem-record
status: fixed
area: rts-inventory
tags: [problem, spa, rts, barcode]
---

# Problem: SPA still treated RTS sellable as case-synced qty

> Hub for Phase 3 SPA of [[specs/2026-08-25_rts-barcode-inventory-cutover]].

## Symptom

After backend barcode cutover, RTS inventory UI still said “synced from records,” offered sellable Use RTS by qty, Record RTS implied saving added sellable stock, and barcode labels pointed staff at packaging scanners.

## Cause

SPA clients and copy were written for case-sync sellable inventory; cutover flags (`can_use_rts_qty`, `barcode_backed_sellable`) and `stock_in_scan` were not wired.

## Fix

- `rtsStockInScan` + inventory_list flag types in `rtsApi.ts`
- RTS inventory: stock-in wedge scan, barcode-backed subtitle, hide sellable Use RTS when `!can_use_rts_qty`
- Record RTS + barcode labels copy: sellable stock-in only on RTS inventory
- POS stock-source / AR: require barcodes for RTS (packaging Non-RTS unchanged)

## Files

| Role | Path / note |
|------|-------------|
| API client | `app/src/app/lib/rtsApi.ts` |
| SPA | `app/src/app/pages/RTSPage.tsx` |
| SPA | `app/src/app/pages/rts/RTSRecordPage.tsx` |
| SPA | `app/src/app/pages/rts/RTSBarcodeLabelsPage.tsx` |
| POS | `POSStockSourceModal`, `POSHoneyPage`, `POSDeptPage`, `POSARCheckoutPanel` |
| Related | [[specs/2026-08-25_rts-barcode-inventory-cutover]] |

## Deploy

- `cd app && npm run build` + hard refresh Apache `app/dist/`

## Links

- Spec: [[specs/2026-08-25_rts-barcode-inventory-cutover]]
- Plan: [[plans/active/2026-08-25_rts-barcode-inventory-cutover_plan]]
