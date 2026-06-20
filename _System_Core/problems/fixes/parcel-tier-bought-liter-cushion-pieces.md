# Fix: Parcel tier bought liter cushion pieces

1. **Reactivate SKUs** — `migration_phase40_custom_cushion_pieces.sql` sets `Cushion piece - 1/2/3 liter` active; phase 39 no longer deactivates them.
2. **Reseed tiers** — `fulfillment_pack_tier_seed.php` uses liter pieces for honey 1L solo, 2L/3L combos with rolls only where Excel has roll columns, PT/BRC 1–2, PT 10–12 (2× 3L piece), cacao jar tiers, and relevant honey/cacao combos.
3. **Reseed DB** — `php tools/seed_fulfillment_pack_tiers.php`.

Gallon bulk tiers (25/50 main columns) and honey comb / LAD / half-LAD rows unchanged.
