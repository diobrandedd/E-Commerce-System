---
date: 2026-08-15
type: problem-record
status: fixed
area: pos-cancel-rts
tags: [problem]
---

# Problem: Dept POS qty sell + tracking cancel could not restock packed barcodes

## Symptom

Paragis/BRC/Cacao/Comb POS sold by quantity (packed stickers could double-deduct). Courier cancel used tracking QR and did not restore finished stock. RTS optional barcodes were honey-only.

## Fix

- Dept POS barcode sell (`pos_lookup` / `pos_consume`); cart remove; packaging qty-only disabled for barcode products.
- Cancelled page → pack cancel restock sessions (phase 61); tracking `cancel_parcel_scan` → 410.
- RTS Record optional product barcodes for honey + dept → sellable/damaged pools.

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase61_pack_cancel_restock.sql` |
| Includes | `includes/dept_pouch_barcodes.php`, `dept_pos.php`, `pack_cancel_restock.php`, `rts_case_handlers.php` |
| API | `api/v1/dept_pouch_barcodes.php`, `pack_cancel_restock.php`, `deliveries.php` |
| SPA | `POSDeptPage.tsx`, `DeliveriesCancelledPage.tsx`, `RTSRecordPage.tsx` |
| Spec | [[specs/2026-08-15_pos-barcode-dept-and-cancel-restock]] |

## Deploy

- Run phase 61 SQL on production
- SPA build + hard refresh
