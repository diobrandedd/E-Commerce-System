# Fix: Jar Cacao foam cut yields on generic insulation foam roll

Executive edits **9"×12"** and **12.5"×12"** pieces-per-roll on the generic `Insulation foam roll` packaging detail modal. Values save to recipe-only sized SKUs (`Insulation foam roll - 9x12` / `12.5x12`) as `pool_yield_qty` with unit `foam_piece`.

Parcel costing: `roll WAC ÷ pool_yield_qty` per cut. Ship deduct uses same yields.

API: `materials.php?action=set_foam_cacao_cut_yields` (executive). Generic roll rejects single `set_pool_yield`.
