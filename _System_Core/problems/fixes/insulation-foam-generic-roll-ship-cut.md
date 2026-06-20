# Fix: Insulation foam generic roll + ship-time cut

1. **Warehouse** — one SKU `Insulation foam roll`; merge sized warehouse on_hand into it (`migration_phase41`).
2. **Issue** — TL issues generic rolls to packing (no cut-size form). Sized foam SKUs blocked from issue/stock-in.
3. **Ship** — cacao parcel recipes still reference `Insulation foam roll - 9x12` / `12.5x12` with `foam_piece` qty; `fulfillment_packing_consume_line` deducts generic staging using sized SKU `pool_yield_qty`.
4. **Costing** — `material_packaging_generic_foam_roll_unit_cost()` ÷ yield per sized line (same pattern as bubble).

Sized SKUs remain for tier recipes and yield config only (`warehouse on_hand = 0`).
