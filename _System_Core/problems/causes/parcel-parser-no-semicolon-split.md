---
type: cause
tags: [cause, parcels, parser]
---

# Cause: Parcel item parser did not split on semicolon

## What went wrong

Mixed lines like `3 Paragis Tea ; 1 Liter Honey AMBER` were parsed as one segment — honey qty/variant leaked across products.

## Where in code

- `includes/honey_parcel_item_parser.php`

## Related problems

- [[records/2026-06-05_parcel-item-parser-semicolon]]
