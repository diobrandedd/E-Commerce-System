# Fix: Bottle units are truth; aggregates are a transactional mirror

`honey_bottle_units` is the unit ledger. Every stock-in scan creates a unit and (for request-sourced batches only) increments the matching `current_*` column in the same transaction. Pack confirm, POS consume, and opened-return reverse that. Backfill batches use `source=backfill` so scan-in adopts without incrementing.

Ship (`online_parcels_ship`) only parses `items_raw` and marks shipped. Honey sales for online orders are written at pack-session confirm (`sale_type=parcel`).
