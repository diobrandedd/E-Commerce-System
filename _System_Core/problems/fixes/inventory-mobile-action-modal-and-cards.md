---
type: fix
tags: [fix, spa, mobile, inventory, ux]
---

# Fix: Inventory pages mobile consistency (action modal + card rows)

## What we changed

Extended the mobile-first pattern from raw materials to dense inventory pages so phone users get readable controls and rows.

## Implementation

- `PackagingMaterialsPage.tsx`
  - Mobile header uses `Guide` + `Actions` only.
  - Added `PackagingMobileActionsModal` for create-only actions on phone.
  - Replaced cramped mobile table rows with stacked card rows (`sm:hidden`), desktop table preserved (`sm:block`).
- `SuppliesMaterialsPage.tsx`
  - Mobile header uses `Guide` + `Actions` only.
  - Added `SuppliesMobileActionsModal` for `Issue request` / `Add item`.
  - Added stacked mobile card rows with clear cost/on-hand line; desktop table remains unchanged.

## Verify

1. Open Packaging and Supplies pages at ~360x800 viewport.
2. Header title remains readable; no action overflow.
3. Tap `Actions` and confirm all role-allowed actions are available.
4. Rows render as mobile cards on phone and as compact table rows on desktop.

## Related problems

- [[records/2026-06-16_inventory-mobile-consistency]]
