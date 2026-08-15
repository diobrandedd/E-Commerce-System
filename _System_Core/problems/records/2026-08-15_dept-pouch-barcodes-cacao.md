# Problem: Jar Cacao needed the same barcode stock-in flow

## Problem
Cacao finished jars were still created via **Produce** (formula deduct, typed qty). Ops wanted the same request → generate/print → scan flow already live for Paragis Tea and Black Rice Coffee.

## Cause
`DEPT_POUCH_BARCODE_KEYS` only listed `pt` and `brc` after the phase58 generalization.

## Fix
- Added `jar_cacao` to `DEPT_POUCH_BARCODE_KEYS` in PHP + SPA.
- `dept_jar_produce` API returns 410; Produce button hidden on Cacao raw page.
- UI copy uses jar/jars via `deptPouchUnitLabel` / `dept_pouch_unit_singular` (print sheet + modals).
- Scan-in still increments finished `dept_inventory_rows` only — no raw/packaging deduct.
- Honey Comb keeps Produce.

No new migration — phase58 `product_key` already supports any dept key.

## Files
- `includes/dept_pouch_barcodes.php`
- `api/v1/honey.php` (gate `dept_jar_produce`)
- `app/src/app/lib/deptPouchBarcodes.ts`
- `app/src/app/components/dept/DeptPouchBarcodePanels.tsx`
- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `app/src/app/lib/deptRawInventory.ts` (drop `deptJarProduce`)
