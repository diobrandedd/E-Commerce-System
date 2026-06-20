---
status: approved
approved: true
spec: specs/2026-06-19_vip-spx-order-interpretation.md
---

# Plan: VIP / SPX order interpretation

## Phase 1 — Column detection
- [`includes/order_remarks.php`](includes/order_remarks.php): J&T + SPX profiles (tracking, item aliases)
- [`includes/order_remarks_workbook.py`](includes/order_remarks_workbook.py): fuzzy headers, tracking + itemText emit

## Phase 2 — Wire VIP prep
- [`orderRemarksApi.ts`](app/src/app/lib/orderRemarksApi.ts), [`VipPrepTool.tsx`](app/src/app/components/csr/onlineOrders/VipPrepTool.tsx): real tracking

## Phase 3 — Interpret + deduct
- [`itemLineBuilder.ts`](app/src/app/lib/itemLineBuilder.ts): compose/decompose lines
- [`orderRemarksParse.ts`](app/src/app/lib/orderRemarksParse.ts): multi-item, gallon, 350ml, promos
- [`honey_parcel_item_parser.php`](includes/honey_parcel_item_parser.php): split combos, 350ml LAD expansion, dept + honey same segment

## Phase 4 — CSR UI + stock
- [`ItemLineBuilder.tsx`](app/src/app/components/csr/onlineOrders/ItemLineBuilder.tsx)
- Update [`AmbiguousOrderCarousel`](app/src/app/components/csr/AmbiguousOrderCarousel.tsx), [`ItemResolverCarousel`](app/src/app/components/csr/onlineOrders/ItemResolverCarousel.tsx)
- [`honeyPackagingStock.ts`](app/src/app/lib/honeyPackagingStock.ts) + [`VariantPresetButton`](app/src/app/components/csr/orderRemarksMotion.tsx): per-variant breakdown

## Phase 5 — Verify + memory bank
