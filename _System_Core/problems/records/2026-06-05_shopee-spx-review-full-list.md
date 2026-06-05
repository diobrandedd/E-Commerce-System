---
date: 2026-06-05
type: problem-record
status: fixed
area: shopee-spx
tags: [problem, shopee-spx, spa]
---

# Problem: Shopee SPX review showed one row instead of full batch

## Symptom

After variant picks, **Review item lines before building Excel** showed only the edited row (e.g. page 13 with `3L-HONEY (LAD) + 350ml (A)`), not the whole packing list.

## Cause

→ [[causes/shopee-review-panel-ambiguous-filter-only]]

## Fix

→ [[fixes/shopee-review-show-all-rows]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `app/src/app/components/csr/onlineOrders/ShopeeItemsReviewPanel.tsx` |
| Parent step | `app/src/app/components/csr/onlineOrders/ShopeeSpxRunStep.tsx` |
| Helpers | `app/src/app/lib/honeyItemVariant.ts` |

## Deploy

- `cd app && npm run build` + hard refresh

## Links

- **Fragile edges:** [[003_Fragile_Edges#Shopee / marketplace PDF item lines]]
- **Patterns:** [[005_SPA_UX_Patterns#CSR variant resolution (ambiguous honey)]]

## AI rules

Do not filter `ShopeeItemsReviewPanel` to ambiguous rows — users need the full batch before export.
