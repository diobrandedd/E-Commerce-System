# Cause: Packaging materials had no barcode / scan stock-out path

Warehouse packaging relied on product-create auto-deduct and row-level manual deduct. `stocks` had no barcode column; list had absolute on-hand only (no % vs last lot). Stock-in was in PM/HR presets.
