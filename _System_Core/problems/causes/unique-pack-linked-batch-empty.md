# Cause: Unique stickers deduct only their linked packaging batch

Each leftover unique unit stores `packaging_row_id`. Pack confirm called `honey_bottle_adjust_aggregate` on that row only. SKU qty (and other FIFO sales) can empty that batch’s `current_*` while the unit stays `in_stock`. Confirm then fails with “Not enough packaging stock to deduct this bottle” even when Products totals show stock on other batches.
