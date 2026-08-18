# Problem: Leak/damage scan, Sales dates, unified POS

## Problem

Staff needed one leak/damage scan for all sticker products (not Jar Cacao only), clearer Sales date filtering, and a POS that auto-detects product from barcode with manual pricing.

## Cause

Features were split: category picker POS, catalog-priced checkout, Jar Cacao–only defect scan, and Sales custom range hidden behind a preset dropdown.

## Fix

- `finished_leak_damage.php` + phase 63 log table + `/inventory/leak-damage` UI (Leaked/Damaged reason).
- **Inventory sidebar** now lists **Leak / damage** (after Products). Honey Packaging has the same shortcut as Paragis/BRC/Cacao.
- Sales page **Pick date** and **Custom range** modes.
- Camera modal now unmounts on close (X / Close / backdrop). Previously Leak/damage and unified POS passed `open`/`onDecode` which the scanner ignored, so the dialog stayed up.

## Files

- `sql/migration_phase63_finished_leak_damage_log.sql`
- `includes/finished_leak_damage.php`, `includes/pos_unified.php`
- `api/v1/finished_leak_damage.php`, `api/v1/pos.php`
- `app/src/app/components/scan/BarcodeCameraScannerModal.tsx`
- `app/src/app/components/Sidebar.tsx` (Inventory → Leak / damage)
- `app/src/app/pages/honey/HoneyPackagingPage.tsx`
- `app/src/app/pages/pos/POSUnifiedPage.tsx`
- `app/src/app/pages/exec/SalesPage.tsx`
- `app/src/app/routes.ts`
