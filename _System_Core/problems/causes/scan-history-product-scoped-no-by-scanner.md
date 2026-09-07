---
date: 2026-09-07
type: cause
area: inventory-scan-history
tags: [cause]
---

# Cause: Scan history was product-scoped without a by-person rollup

Finished stock-in history APIs already returned **all scanners** for a `product_key` (not filtered to the logged-in user), but:

1. The UI opened from one raw-material page only for that product.
2. There was no **by person** breakdown — the table “Who” column was **requested_by**, not who scanned.
3. SKU stock events were incomplete in the per-batch scan log.
4. Honey bottle packaging history had no by-scanner summary.
