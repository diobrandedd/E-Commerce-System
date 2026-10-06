---
status: approved
created: 2026-10-06
---

# Feature Spec: Packaging materials barcodes + scan stock-out

## Problem

Warehouse packaging stock-out relied on auto-deduct during product creation and manual row deduct. Materials had no scannable barcodes, no stock-level %, and stock-in was shared with HR/PM.

## Goals

1. Permanent Code128 barcode per packaging catalog row; **Generate all** on one A4 sheet with **name under** the bars.
2. Scan stock-out: scan → confirm → qty + reason → `packaging_deduct` (permission `packaging.deduct`).
3. Row **level %** = `on_hand / last stock-in lot qty × 100` (latest `lot:%` entry).
4. **Stock-in executive only**; page shows who can stock out.
5. **Remove** auto packaging materials deduct on honey bottling / dept produce. Fulfillment staging ship deduct unchanged.

## Non-goals

- Separate assignment table
- Changing POS finished-honey packaging pool deduct
- PDF label engine (reuse HTML A4 packer)

## Acceptance

- Active packaging rows have barcodes; Generate all prints A4 with names under bars.
- Users with `packaging.deduct` can scan-deduct; others cannot.
- Only executives can stock-in lots.
- List shows level % when a prior lot exists.
- Bottling / dept produce no longer reduce `stocks.on_hand` for packaging materials.
