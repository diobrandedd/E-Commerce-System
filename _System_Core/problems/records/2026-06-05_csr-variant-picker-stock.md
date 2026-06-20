---
date: 2026-06-05
type: problem-record
status: fixed
area: csr
tags: [problem, csr, inventory, enhancement]
---

# Problem: CSR variant pickers lacked stock indicators

## Symptom

No on-hand counts when picking Light/Amber/Dark in Shopee/VIP/Order Remarks flows.

## Cause

→ [[causes/variant-picker-no-stock-at-pick-time]]

## Fix

→ [[fixes/packaging-stock-on-preset-buttons]]

## Files

| Role | Path |
|------|------|
| API | `api/v1/online_orders.php` (`packaging_stock`) |
| SPA | `honeyPackagingStock.ts`, `useHoneyPackagingStock.ts`, `ItemResolverCarousel.tsx`, `AmbiguousOrderCarousel.tsx` |

## Deploy

- SPA build

## Links

- **Patterns:** [[005_SPA_UX_Patterns#CSR variant picker stock indicators]]
- **Fragile edges:** [[003_Fragile_Edges#CSR packaging stock for variant pickers]]
