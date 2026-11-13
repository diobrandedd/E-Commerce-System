---
type: fix
---

# Fix: Check Inventory supplies kind

- Phase 98 adds `kind=supply` to scan/variance ENUMs.
- Check-only `inventory_check_lookup` tries `sku_barcode_lookup` then `material_packaging_lookup_by_barcode` (shared SKU lookup unchanged).
- Scan upserts supply lines with `barcode_id`/`sku_id` NULL; compare uses `stocks.on_hand`; apply calls `material_packaging_set_on_hand` with source `inventory_check_apply`.
- SPA shows Supplies badges on pending qty, session list, compare, and history/applied views.
