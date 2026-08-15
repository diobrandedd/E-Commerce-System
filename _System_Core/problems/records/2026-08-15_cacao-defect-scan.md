---
date: 2026-08-15
area: inventory-defect
spec: specs/2026-08-15_cacao-defect-scan
---

# Jar Cacao finished-jar defect scan + history

## Problem

Damaged Jar Cacao jars that were already scanned into finished stock had no barcode path to remove
them from inventory. Staff needed a scan that only works when the jar is in stock, a clear
**Not in inventory** message otherwise, a defect history, and no RTS pool handoff.

## Cause

Dept pouch barcodes supported stock-in, pack, POS, and RTS receive — not a finished-goods defect
path. There was no `dept_pouch_defect_log` table.

## Fix

- Phase 62: `dept_pouch_defect_log` (product, barcode, unit, code, batch, scanner, time).
- `dept_pouch_defect_scan` / `dept_pouch_defect_history` gated by `DEPT_POUCH_DEFECT_KEYS`
  (`jar_cacao`). Success: unit → `damaged`, `consumed_ref_type=defect`, finished qty −1, log row.
  Non-`in_stock` / missing unit → **Not in inventory**. No RTS pool write.
- API `defect_scan` / `defect_history`; SPA `DeptPouchDefectPanel` (Scan + History) on the Jar Cacao
  raw page **Defect** button only.

## Files

- `sql/migration_phase62_dept_pouch_defect_log.sql`
- `includes/dept_pouch_barcodes.php`
- `api/v1/dept_pouch_barcodes.php`
- `app/src/app/lib/deptPouchBarcodes.ts`
- `app/src/app/components/dept/DeptPouchDefectPanel.tsx`
- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `app/src/app/components/inventory/DeptRawProduceGuideModal.tsx`
