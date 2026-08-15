# Problem: Honey batch label could not be reused across variants

## Problem
Ops use one supplier batch label (e.g. `JR063026`) for multiple variants and sizes. Stickers must stay unique, but creating a second packaging row with the same `batch_no` for another variant failed on `uq_honey_pk_batch_no`.

## Cause
`honey_packaging_rows.batch_no` was globally UNIQUE. Collision helpers (`honey_bottle_unique_batch_no`, custom create) also checked `batch_no` alone.

## Fix
- Migration phase59: drop `uq_honey_pk_batch_no`; add `uq_honey_pk_batch_variant` on `(batch_no, variant)`.
- `honey_unique_packaging_batch_no($pdo, $base, $variant)` allocates `-2`… only within that variant.
- Custom create: same code + same variant → “pick from dropdown”; same code + other variant → allowed.
- Re-requesting more stickers for the same batch+variant+size was already allowed; inventory still only increases on scan.

## Files
- `sql/migration_phase59_honey_batch_no_per_variant.sql`
- `includes/honey_inventory.php`
- `includes/honey_bottle_barcodes.php`
