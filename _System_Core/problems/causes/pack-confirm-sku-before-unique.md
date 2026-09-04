# Cause: Pack confirm processed SKU qty before unique stickers

Mixed pack sessions scanned items in scan order. SKU lines deduct packaging via FIFO across batches; unique stickers deduct 1 from their **linked** `packaging_row_id` only.

If a SKU qty line ran first, it could empty the aggregate on the same batch a later unique sticker still needed → `Not enough packaging stock to deduct this bottle`, even when Products totals looked fine. Unique-only or SKU-only sessions did not hit this race.
