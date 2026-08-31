---
date: 2026-08-31
type: problem-record
status: fixed
area: fulfillment
tags: [barcode, labels, sku]
---

# Problem: Generated barcode sheets showed BATCH A/B

## Symptom

Generated barcode sheets displayed generic `BATCH A` or `BATCH B` banner
text instead of identifying the requested size/variant or named product.

## Cause

→ [[causes/barcode-sheet-generic-batch-banner]]

## Fix

→ [[fixes/barcode-sheet-requested-item-banner]]

## Files

| Role | Path / note |
|------|-------------|
| Shared renderer | `includes/sku_barcodes.php` |
| RTS caller | `includes/rts_barcode_labels.php` |

## Deploy

- Deploy the PHP files.
- Open a fresh generated sheet or hard-refresh the SPA before printing.
- Verify Honey shows size + variant and departments show product name.
