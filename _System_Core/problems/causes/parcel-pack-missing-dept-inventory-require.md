---
type: cause
tags: [cause, php, includes]
---

# Cause: `parcel_pack_costing.php` called `dept_inventory_resolve_other_item` without require

## What went wrong

`parcel_pack_order_shape_from_parsed()` calls `dept_inventory_resolve_other_item()` which lives in `dept_inventory.php`, but only `dept_products.php` was required. Fatal on preview when parsing dept lines in Item/s string.

## Where in code

- `includes/parcel_pack_costing.php`

## Related problems

- [[records/2026-06-05_parcel-pack-ship-bugs]]

## Prevent

- `require_once __DIR__ . '/dept_inventory.php'` in `parcel_pack_costing.php`.
- Run `impact.php` / grep callers when adding cross-include helpers.
