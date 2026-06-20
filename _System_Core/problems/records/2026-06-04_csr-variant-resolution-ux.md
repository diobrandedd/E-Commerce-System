---
date: 2026-06-04
type: problem-record
status: fixed
area: csr
tags: [problem, shopee-spx, vip]
---

# Problem: Ambiguous honey variant picks lacked confirm/review UX

## Symptom

Wrong variant could auto-commit; VIP Show completed did nothing; confirm crash `trim is not a function`.

## Cause

→ [[causes/variant-pick-auto-committed-and-show-completed-broken]]

## Fix

→ [[fixes/confirm-review-edit-variant-flow]]

## Files

| Role | Path |
|------|------|
| Shopee | `ItemResolverCarousel.tsx`, `ShopeeSpxRunStep.tsx`, `ShopeeItemsReviewPanel.tsx` |
| VIP | `AmbiguousOrderCarousel.tsx`, `VipRunStep.tsx` |
| Helpers | `honeyItemVariant.ts` |

## Links

- **Patterns:** [[005_SPA_UX_Patterns#CSR variant resolution (ambiguous honey)]]
