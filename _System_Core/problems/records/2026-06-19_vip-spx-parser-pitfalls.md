---
date: 2026-06-19
type: problem-record
status: fixed
area: csr-order-remarks
tags: [problem, vip, spx, parser, obsidian]
---

# Problem: VIP/SPX parser pitfalls — combo drop, false L, missing LLA

> **Obsidian hub** for 2026-06-19 follow-up fixes after [[records/2026-06-19_vip-spx-order-interpretation|initial VIP/SPX ship]]. Read this before changing order remark parsing.

## Symptom

1. **Honey + dept combo** exported as dept only (`1 Jar Cacao` instead of `1 x 1L Honey (L) + 1 Jar Cacao`) — SPX `35c97a2762f34c02b4545be4220e0d37.xlsx` rows 3 & 9.
2. **3L Set B** with `2 lights and 1 amber` showed **LLA** on the line but **Pick variant** — LLA missing from dropdown.
3. Long multi-line cards stretched the carousel — fixed with collapsible lines + scroll.
4. **J&T bundled 350ml** dropped on export — e.g. Wealthyneil row 22: `3L HONEY(ALL DARK)1 PC 350ML(DARK)` → only `1 x 3L Honey (DDD)` (missing `+ 1× 350ml (D)`).

## Causes

| Issue | Cause note |
|-------|------------|
| Export dropped honey | [[causes/vip-spx-combo-export-drops-honey]] |
| False auto-resolve / wrong export | [[causes/vip-spx-size-letter-false-variant]] |
| LLA not selectable | [[causes/vip-spx-3l-variant-list-incomplete]] |
| J&T 350ml dropped | [[causes/vip-spx-jnt-concatenated-350ml]] |

## Fix

→ [[fixes/vip-spx-order-remarks-parser-guards]]

## Files

| Role | Path |
|------|------|
| Parser core | `app/src/app/lib/itemLineBuilder.ts` |
| VIP resolve / export | `app/src/app/lib/orderRemarksParse.ts` |
| Stock labels | `app/src/app/lib/honeyPackagingStock.ts` |
| Shopee hints | `app/src/app/lib/honeyItemVariant.ts` |
| PHP parity | `includes/honey_parcel_item_parser.php` |
| Builder UI | `app/src/app/components/csr/onlineOrders/ItemLineBuilder.tsx` |
| VIP carousel | `app/src/app/components/csr/AmbiguousOrderCarousel.tsx` |
| SPX carousel | `app/src/app/components/csr/onlineOrders/ItemResolverCarousel.tsx` |
| Waybill stamp | `waybill-stamper/processor.py` — text-only stamp → [[fixes/waybill-stamp-text-only-no-border]] |

## Deploy

- `cd app && npm run build` + hard refresh CSR Online Orders
- PHP includes only — no migration

## Links

- **Fragile edges:** [[003_Fragile_Edges#VIP / SPX order remarks parser (CSR)]]
- **Spec:** [[specs/2026-06-19_vip-spx-order-interpretation]]
- **Parent record:** [[records/2026-06-19_vip-spx-order-interpretation]]

## AI rules (do not repeat)

1. **Never** treat `L` in `1 L HONEY` as Light — strip size tokens first.
2. **Never** filter incomplete honey out of `composePackerLine` for export fallback.
3. **Always** sync 3L codes across TS + PHP when adding a mix (see LLA table in cause note).
4. **Multi-item combos** need explicit ItemLineBuilder save (`finalRemark`) before export.
5. **250ml** is a real sellable size — treat like 350ml in parser/builder/stock.
6. **J&T concatenated Item Name** — split `3L HONEY(…)1 PC 350ML(…)` before parsing; see [[causes/vip-spx-jnt-concatenated-350ml]].
