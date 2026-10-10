---
type: cause
tags: [cause]
---

# Cause: Pack and cancel touched leftover lots, Honey tab reads pools

## What went wrong

Products → Honey sums only `batch_no=POOL` rows. Pack confirm and cancel restock deducted or credited the sticker’s old packaging row (or any row with qty). Inventory check summed every packaging row, so the audit moved while the Honey tab did not.

## Where in code

- `honey_bottle_deduct_packaging_one()` / `sku_barcode_honey_deduct()` FIFO
- `pack_cancel_restock_confirm()` credit on `packaging_row_id`
- `inventory_check_compare_honey_lines()` unfiltered SUM

## Related problems

- [[records/2026-10-10_honey-pool-pack-cancel]]
