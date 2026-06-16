---
type: cause
tags: [cause, php, parcel-pack-costing]
---

# Cause: `static` closure in `parcel_pack_fetch_tier_lines` did not capture `$pdo`

## What went wrong

`array_map(static function (array $row): array { ... parcel_pack_line_unit_cost($pdo, $row) ...})` referenced `$pdo` without `use ($pdo)`. PHP passed `null` → fatal `Argument #1 ($pdo) must be of type PDO, null given` → API **500** on `parcel_tiers_list`, `products_list&parcel=1`, and preview.

## Where in code

- `includes/parcel_pack_costing.php` — `parcel_pack_fetch_tier_lines()`

## Related problems

- [[records/2026-06-05_parcel-pack-ship-bugs]]

## Prevent

- Any `static function` inside a function that needs outer variables must use `use ($var)`.
- After adding parcel costing endpoints, smoke-test: `parcel_tiers_list` + `products_list&parcel=1` before shipping UI.
