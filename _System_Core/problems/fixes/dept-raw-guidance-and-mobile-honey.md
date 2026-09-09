---
type: fix
tags: [fix, raw-materials, honey, paragis, mobile, accessibility]
---

# Fix: Dept raw guidance and mobile Honey inventory

## What we changed

- Dried Paragis now distinguishes optional raw/WIP records from inventory-only finished pouch barcode stock-in.
- Honey Warehouse, Production, and Packaging use stacked cards on phones and retain tables on larger screens.
- Packaging scan details use semantic buttons; trace search and dialogs have accessible names.
- Raw-material errors use animated alerts and retry actions; shared `AppModal` traps focus, closes on Escape, and restores focus.
- The final Impeccable polish makes finished pouch scanning the primary Paragis phone action, moves secondary production and waste tools into a focused actions modal, and removes stale row-tap guidance.
- Honey stage tabs now preserve visual continuity with a moving active pill and directional stage-body transition, with an immediate reduced-motion path.

## Implementation

- Added mobile card/table breakpoints and 44 px phone targets.
- Replaced click-only Packaging rows with explicit Details actions.
- Standardized load and mutation errors with `AppErrorBanner`.
- Raised remaining scanner, barcode-request, and mobile supplier controls to the 44 px touch floor.
- Added desktop and 360 × 800 Playwright route coverage.

## Verify

Run:

`npx playwright test tests/inventory/raw-materials.spec.ts --project=chromium --project=mobile-chrome`

## Related problems

- [[records/2026-09-09_raw-materials-ui-fix]]
