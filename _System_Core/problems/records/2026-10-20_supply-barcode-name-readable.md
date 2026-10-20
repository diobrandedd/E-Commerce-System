---
date: 2026-10-20
type: problem-record
status: fixed
area: packaging-supplies
tags: [supplies, barcode, print]
---

# Problem: Supply names on the barcode sheet were unreadable

## Symptom

Generate all barcodes printed each supply name at 6.5pt on one clipped line inside an 8-across sticker cell.

## Cause

The supplies sheet reused the product sticker grid (8×20). That layout is sized for a short code, not a supply name.

## Fix

Supplies print on a 4×10 A4 grid. The name is 11pt and wraps to two lines, with the `PKG` code under it. Product sticker sheets stay 8×20.

## Files

| Role | Path |
|------|------|
| Include | `includes/material_inventory.php` |
| Include | `includes/sku_barcodes.php` (`sku_barcode_render_packed_label_sheet` layout argument) |

## Deploy

- PHP only. Hard-refresh is not required for the print sheet; open Generate all barcodes again.
