# Fix: Unique pack/POS deduct with FIFO fallback

`honey_bottle_deduct_packaging_one` tries the unit’s linked packaging row first; if that size column is empty, deducts 1 from any other packaging row of the same variant+size (FIFO by id). Wired into pack session confirm and POS unique-bottle sell.
