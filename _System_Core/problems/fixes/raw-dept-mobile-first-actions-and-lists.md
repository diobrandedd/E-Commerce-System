---
type: fix
tags: [fix, spa, mobile, inventory, raw-materials]
---

# Fix: Raw dept page mobile-first actions and activity lists

## What we changed

Made `/inventory/raw-materials/*` usable on phones by reducing header action density and replacing tiny multi-column tables with stacked mobile entries.

## Implementation

- `RawDeptMaterialPage.tsx`
  - Added compact mobile header controls: `Finished`, `Guide`, `Actions`.
  - Added `RawActionsModal` as a phone-friendly quick action hub (Stock in, Use raw bags/Pouch/WIP waste/Produce/Bag waste).
  - Kept full button set on `sm+` desktop/tablet.
  - Changed top stats to `grid-cols-1` on phones (then `sm:grid-cols-2`).
  - Added mobile card render for **Production activity** and **Raw bag entries**; desktop table now `hidden sm:block`.

## Verify

1. Open `/inventory/raw-materials/paragis` in mobile viewport (~360x800).
2. Header title remains readable; actions no longer overflow.
3. Tap **Actions** and confirm all role-allowed operations are present and enabled/disabled correctly.
4. Activity sections show stacked cards on phone and still show tables on desktop.

## Related problems

- [[records/2026-06-16_raw-dept-mobile-layout]]
