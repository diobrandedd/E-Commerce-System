---
status: approved
created: 2026-09-18
owner: ops
area: inventory-check
tags: [inventory-check, pools, barcodes]
---

# Check Inventory: drop required batch / bottling date

> After honey packaging pools, finished matching is by variant + size (or dept product). Barcodes already carry that — auditors should scan then enter quantity only.

Links: [[specs/2026-09-17_honey-packaging-pool-no-lots]] · [[specs/2026-09-11_inventory-check-batch-docs]] · [[003_Fragile_Edges]]

## Locked behavior

1. **Unique stickers** (honey / dept): scan commits immediately (+1). Variant/size/product from barcode resolve. Optional batch/date may be stored from sticker metadata when present; never required from the auditor.
2. **SKU barcodes**: scan → enter quantity → commit. Rescan same SKU code in the same session adds to `counted_qty` (one row per `event_id` + `code`).
3. Compare / stock apply unchanged: still matched by product / variant / size totals, not by batch docs.
4. Edit line: quantity only (no batch/date fields).
5. Batch documentation UI may show empty or leftover historical docs; not part of the required flow.

## Out of scope

Rewriting historical scan rows; changing compare math; Packaging stocked-in report.

## Approval

- [x] User chat 2026-09-18 — “scan barcode then enter quantity”
