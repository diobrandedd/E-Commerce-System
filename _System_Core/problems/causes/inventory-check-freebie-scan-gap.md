---
date: 2026-11-05
type: cause
status: documented
area: inventory-check
tags: [cause]
---

# Cause: Check inventory scan ignored freebie lookup_kind

`onAddScan` only branched on `sku` and `unique_*`. `sku_barcode_lookup` already returned `lookup_kind: "freebie"`, and `inventory_check_scan` already added quantity to `FREEBIE:{product_key}`, but the SPA never entered the pending-qty flow for freebies.
