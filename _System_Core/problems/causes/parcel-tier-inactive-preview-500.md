---
type: cause
tags: [cause, parcel-pack-costing, api]
---

# Cause: Preview by tier key used `activeOnly` and threw on TBD tier

## What went wrong

`honey_250_350_solo` is seeded with `is_active = 0`. `parcel_pack_tier_cost_estimate_by_key()` called `parcel_pack_fetch_tier_by_key(..., activeOnly: true)` → tier not found → **500** when user picked "Honey - 250/350 solo (TBD)" in preview dropdown.

## Where in code

- `includes/parcel_pack_costing.php` — `parcel_pack_tier_cost_estimate_by_key`
- `api/v1/costing.php` — `parcel_cost_preview`

## Related problems

- [[records/2026-06-05_parcel-pack-ship-bugs]]

## Prevent

- Use `parcel_pack_preview_tier_by_key()` for explicit preview: fetch inactive tier, return zero cost + warning if `parcel_pack_tier_is_placeholder()`.
- Exclude placeholder tiers from preview dropdown in SPA (`!is_active` or `honey_250_350_solo` rule).
