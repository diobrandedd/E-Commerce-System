---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [inventory-check, scan, audit, finished-goods]
---

# Check Inventory: scan-only Add to list (no batch)

> Add-to-list is continuous scan or typed qty only. No batch field. Compare matches finished on-hand (honey variant+size / dept product). 500ms inter-scan cooldown + success popup with item name.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[005_SPA_UX_Patterns]] · [[plans/archive/2026-09-10_inventory-check-scan-only_plan]] · [[problems/records/2026-09-10_inventory-check-scan-only]]

## Locked behavior (chat 2026-09-10)

1. Remove batch number from Add-to-list UI.
2. API `batch_no` optional; unique may keep barcode batch as metadata.
3. Honey compare aggregates by **variant + size** (sum packaging `current_*`); dept stays product-level.
4. 500ms cooldown between accepted scans; success feedback names the item.

## Success criteria

- [x] Add modal has no batch field.
- [x] SKU/unique scan works without batch.
- [x] Compare honey lines are variant+size aggregates.
- [x] Success popup shows item label; 500ms gate between scans.

## Non-goals

- Changing stock on complete
- Matching packaging materials lots
