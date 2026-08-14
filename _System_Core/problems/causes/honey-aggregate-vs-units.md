# Cause: Honey inventory lived only on aggregate columns

Bottled stock was `current_liter` / `current_250ml` / `current_350ml` / `current_gallon` on `honey_packaging_rows`. Deduction was FIFO by variant+size. Sales log inserts left `packaging_row_id` and `batch_no` NULL, so batch traceability never showed POS or parcel sales.
