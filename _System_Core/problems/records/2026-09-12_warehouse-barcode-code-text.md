# Problem: Warehouse raw honey labels missing readable code

**Date:** 2026-09-12

## Problem

Printed supplier/date warehouse barcodes showed bars only — no alphanumeric code under them for manual visibility.

## Cause

`sku_barcode_label_cell_html()` with `$product === null` rendered a bare `.label-plain` barcode cell (no `.human-code`). Honey warehouse print uses that path.

## Fix

Always render `.human-code` under plain labels; tighten plain-label CSS so bars + code fit the 8×20 cell. Toolbar hint notes “barcode + code text”.

## Files

- `includes/sku_barcodes.php`
