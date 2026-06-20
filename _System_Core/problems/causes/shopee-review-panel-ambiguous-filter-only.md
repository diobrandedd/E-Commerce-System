---
type: cause
tags: [cause, shopee-spx]
---

# Cause: Shopee review panel filtered to ambiguous rows only

## What went wrong

`ShopeeItemsReviewPanel` built its table from `rows.filter(r => shopeeRowNeedsItemResolution(...))`. Only rows that still needed a variant pick or had empty items appeared. Auto-resolved packing-list lines were hidden, so the user saw a single edited row before **Confirm all & build Excel**.

## Where in code

- `ShopeeItemsReviewPanel.tsx` — `reviewRows` filter
- `shopeeRowNeedsItemResolution()` in `app/src/app/lib/honeyItemVariant.ts`

## Related problems

- [[records/2026-06-05_shopee-spx-review-full-list]]

## Prevent

Review gate must list **every row** — see [[003_Fragile_Edges#Shopee / marketplace PDF item lines]].
