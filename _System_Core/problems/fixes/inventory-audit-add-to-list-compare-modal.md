---
date: 2026-09-05
type: fix
status: implemented
area: inventory-check
tags: [fix]
---

# Fix: Inventory audit Add-to-list + compare modal

1. `inventory_check_scan` requires `batch_no`; SKU upsert by code+batch; unique stickers must match barcode batch.
2. `scan_update` for open-event qty/batch edits; `compare_preview` (no write); `finalize` = compare_run + event_close.
3. SPA: session list, Add-to-list `AppModal` (camera/scan/qty/batch), Complete compare modal with Go back / Confirm and save.
