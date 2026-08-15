# Problem: Black Rice Coffee needed the Paragis pouch barcode flow

## Problem
Black Rice Coffee finished pouches were still created by the **Produce** button (formula deduct, typed quantity). Ops wanted the same request → generate/print → scan-in stickers with custom batch codes that Paragis Tea got in phase57.

## Cause
The phase57 pipeline was written Paragis-only: tables named `paragis_pouch_*`, and every function/endpoint hardcoded product key `pt`. Copying it per product would have duplicated ~575 lines of PHP plus the API, SPA client, and panels.

## Fix
Generalized the pipeline to any dept product instead of duplicating it.

- Migration phase58 **renames** `paragis_pouch_*` → `dept_pouch_*` (rows preserved) and adds `product_key` to requests, batches, and units, defaulting to `pt` so existing Paragis data is unchanged.
- `includes/dept_pouch_barcodes.php` takes `$productKey`; `DEPT_POUCH_BARCODE_KEYS = ['pt','brc']` gates which products expose the flow.
- Codes stay unique **across** products (shared `dept_pouch_codes_issued` ledger), and `dept_pouch_scan_stock_in()` rejects a sticker scanned on the wrong product's panel rather than crediting the wrong stock.
- `dept_brc_produce` API returns 410, matching `dept_pt_pouch`. Black Rice loses the Produce button; jar cacao and honey comb keep it.
- Scan-in still only increments `dept_inventory_rows` — no raw bag or packaging deduct.

## Files
- `sql/migration_phase58_dept_pouch_barcodes.sql`
- `includes/dept_pouch_barcodes.php` (replaces `includes/paragis_pouch_barcodes.php`)
- `api/v1/dept_pouch_barcodes.php` (replaces `api/v1/paragis_pouch_barcodes.php`)
- `api/v1/honey.php` (gate `dept_brc_produce`)
- `app/src/app/lib/deptPouchBarcodes.ts` (replaces `paragisPouchBarcodes.ts`)
- `app/src/app/components/dept/DeptPouchBarcodePanels.tsx` (replaces `paragis/ParagisPouchBarcodePanels.tsx`)
- `app/src/app/lib/honeyPermissions.ts` (`canParagisPouchBarcodeOps` → `canDeptPouchBarcodeOps`)
- `app/src/app/lib/deptRawInventory.ts` (drop retired `deptPtPouch` / `deptBrcProduce`)
- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `app/src/app/components/inventory/DeptRawProduceGuideModal.tsx`, `app/src/app/components/tour/guides/deptRawProduceTour.ts`

## Adding another product later
Add the key to `DEPT_POUCH_BARCODE_KEYS` in **both** `includes/dept_pouch_barcodes.php` and `app/src/app/lib/deptPouchBarcodes.ts`. No schema change is needed.
