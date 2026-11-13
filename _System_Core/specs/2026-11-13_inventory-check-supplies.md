---
status: approved
created: 2026-11-13
approved_by: chat
---

# Feature Spec: Inventory check — Supplies

## Problem

Supplies (packaging materials on `/inventory/supplies`) have `PKG…` barcodes and `stocks.on_hand`, but Check Inventory only resolves honey / dept / freebie. Corrections require manual set-on-hand on the Supplies page. Ops need the same scan → count → compare → executive apply loop used for finished goods.

## Goals

1. Scan any Supplies catalog barcode (`stocks.category = packaging`) in Check Inventory; future creates work automatically via lookup.
2. Show a clear **Supplies** type badge on pending qty, session lines, compare, and history/applied views.
3. Compare counted qty vs `stocks.on_hand` (scoped to scanned lines only).
4. Executive finalize / merge apply sets `on_hand` to counted qty (admin password already on finalize) and writes Logs with source `inventory_check_apply`.

## Non-goals

- HR `supply` category, `raw_material`, `costing_only` (no PKG barcodes).
- Decimal counted qty (INT only; unit = `stocks.unit`).
- Zeroing unscanned supplies.
- Changing shared `sku_barcode_lookup` (pack-scan / honey / dept panels must not accept PKG codes).
- New permission keys.

## Acceptance

- PKG barcode → qty prompt with Supplies badge + name + unit + system on_hand.
- Same code on Honey Pack Scan stays "Unknown barcode."
- Compare shows supply variances; finalize updates Supplies page on_hand.
- Logs show inventory-check apply with item name.
- Inactive supply rejected at scan; finalize names the item if deactivated mid-audit.
