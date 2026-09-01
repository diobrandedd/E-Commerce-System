# Problem: SKU hex color barcodes + legacy alias scan

## Problem

Permanent SKU stickers moved from text codes (`350mlL`, `1LiterD`, `1PouchPT`) to 6-digit hex color values (`F54927`, `060000`, `00FF00`). Stickers already printed with old codes (e.g. Light 350ml ×152 on 2026-09-01) must still scan; unique 6-char unit stickers must remain unchanged.

## Cause

`product_sku_barcodes.code` held legacy text; no alias map when codes changed.

## Fix

- `sku_barcode_default_codes()` — canonical hex per catalog slot (settings + empty rows).
- `sku_barcode_legacy_aliases()` + `sku_barcode_fetch_legacy_alias()` — old text codes resolve to same SKU row after DB code update.
- `sql/migration_phase73_sku_hex_codes.sql` — updates all 16 rows.

## Files

- `includes/sku_barcodes.php`
- `sql/migration_phase73_sku_hex_codes.sql`
- `_System_Core/problems/fixes/sku-barcode-permanent-catalog.md` (note)

## Verify

- `350mlL` → Light · 350ml (canonical `F54927`)
- `473600` → Amber · 350ml
- Unique codes still fall through to unit lookup when not SKU/legacy
