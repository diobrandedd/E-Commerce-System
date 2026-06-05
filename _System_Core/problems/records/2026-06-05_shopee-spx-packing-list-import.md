---
date: 2026-06-05
type: problem-record
status: fixed
area: shopee-spx
tags: [problem, shopee-spx, enhancement]
---

# Problem: No Shopee packing list Excel import for order_no and items

## Symptom

Shopee SPX flow was PDF-only; packing list Excel with `order_sn` and `product_info` could not drive master Excel.

## Cause

→ [[causes/shopee-pdf-only-missing-order-no-and-names]]

## Fix

→ [[fixes/shopee-packing-list-excel-plus-pdf-import]]

## Files

| Role | Path / note |
|------|-------------|
| Parser | `includes/shopee_spx_product_info.php` |
| Import | `includes/shopee_spx_packing_list.php` |
| Generator | [[generated/modules/online_orders_generator]] |
| SPA | `ShopeeSpxRunStep.tsx`, `honeyItemVariant.ts` |

## Deploy

- PHP + SPA build

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Shopee SPX packing list import]]
- **Fragile edges:** [[003_Fragile_Edges#Shopee SPX packing list product_info]]
