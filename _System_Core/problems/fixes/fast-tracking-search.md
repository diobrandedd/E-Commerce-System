---
type: fix
tags: [fix, performance, search, tracking]
---

# Fix: Fast tracking number search

## Problem

Deliveries review, RTS lists, and Online parcels felt slow when searching tracking numbers because every keystroke hit the API with leading-wildcard `LIKE '%q%'`.

## Fix

- Debounce search input (~300ms) via `useDebouncedValue`.
- TN-shaped queries (length ≥ 8, no spaces) use indexed `=` / prefix `LIKE 'q%'` (`includes/tracking_search.php`).
- Soft / name / short queries keep contains-style LIKE (debounced).
- Online parcels: while `q` is set, list `per_page` capped at 100 even if UI preset is 1k/10k.

## Files

- `app/src/app/hooks/useDebouncedValue.ts`
- `includes/tracking_search.php`
- Deliveries / RTS / parcels list query + SPA wiring
