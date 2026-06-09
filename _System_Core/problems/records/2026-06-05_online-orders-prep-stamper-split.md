---
date: 2026-06-05
type: problem-record
status: fixed
area: csr-online-orders
tags: [problem, online-orders, spx, vip, waybill-stamper]
---

# Problem: Online Orders prep vs stamp split + SPX packing-list follow-ups

> Consolidates post-remake UX and `shopee_spx_product_info.php` parsing fixes from the same sprint.

## Symptom

1. VIP tool felt like it led to stamping; users only wanted an **arranged Excel** download.
2. No dedicated **PDF + Excel stamper** on the tools page — stamp was tied to each prep tool.
3. SPX packing list: gallon rows ignored variant; `Quantity: 2` overrode `1 Gallon` in product title; multi-item `[1][2]` cells only parsed first line; B2T1 350ml misread as `3L-HONEY (LAD) + 350ml`.
4. SPX review **Edit** did nothing on auto-resolved rows; edit flow showed variant presets instead of free-text.
5. Users could not download prepared Excel before stamping to check errors.

## Cause

- Initial remake wired all prep tools → `UnifiedStampStep` in one flow.
- `shopee_spx_build_items_from_product_info()` lacked gallon/multi-block/B2T1-350ml/title-qty rules.
- `ItemResolverCarousel` only listed rows where `shopeeRowNeedsItemResolution()` — packing-list auto-complete rows were excluded from edit.
- No `waybill.php?action=stash_download` for prep Excel review without releasing stash tokens.

## Fix

### Tools page (4 cards)

| Tool | Behavior |
|------|----------|
| VIP Excel arranger | Remarks → iPYX stash + download → **Back to tools** (no stamp) |
| SPX name merge | Import/review → build Excel → download → **Back to tools** |
| Validate existing Excel | Register + download → **Back to tools** |
| **PDF + Excel stamper** | Load Excel + PDFs, download check, `run_unified_stamp` → batch |

SPA: `WaybillStamperTool.tsx` replaces `UnifiedStampStep.tsx`. `ToolsPicker` 2×2 grid.

### Backend / parsing (`includes/shopee_spx_product_info.php`)

- `shopee_spx_split_product_info_blocks()` — `[1]…[2]…` → ` ; ` merged lines
- Gallon: `{N} Gallon Honey {VARIANT}` from title + variation (not raw Shopee Quantity when title has `N Gallon`)
- `shopee_spx_packer_qty_from_name()` — prefer product-title size over `Quantity:` field
- B2T1 350ml vs 1L: `shopee_spx_is_b2t1_350ml_promo()` → `3× 350ml (LAD)` not 3L combo

### API

- `waybill_stamper_serve_stash_download()` + `waybill.php?action=stash_download`

### SPX review edit

- `ItemResolverCarousel`: `focusPage` injects auto-resolved rows; review edit = **type-only** text field + Save
- Returns to review table after save

## Files

| Role | Path |
|------|------|
| SPX parse | `includes/shopee_spx_product_info.php` |
| Stash download | `includes/waybill_stamper.php`, `api/v1/waybill.php` |
| Stamper tool | `app/src/app/components/csr/onlineOrders/WaybillStamperTool.tsx` |
| Prep tools | `VipPrepTool.tsx`, `SpxPrepTool.tsx`, `ExcelUploadTool.tsx`, `ToolsPicker.tsx` |
| Page | `app/src/app/pages/csr/OnlineOrdersGeneratorPage.tsx` |
| Review edit | `ItemResolverCarousel.tsx`, `ShopeeItemsReviewPanel.tsx` |
| Client | `app/src/app/lib/honeyItemVariant.ts`, `waybillApi.ts` |
| Removed | `UnifiedStampStep.tsx`, `PipelinePicker.tsx`, per-pipeline run steps |

## Deploy

- `cd app && npm run build` + hard refresh `app/dist/`
- `php tools/memory_bank/generate_all.php`

## Links

- **Prior:** [[problems/records/2026-06-05_online-orders-two-phase-remake]]
- **Prior:** [[problems/records/2026-06-05_spx-gallon-variant-qty]]
- **Fragile:** [[003_Fragile_Edges#Shopee SPX packing list product_info]], [[003_Fragile_Edges#Online Orders stamp UX (SPA)]]
- **Plan:** [[plans/archive/2026-06-05_waybill_stamper_and_online_orders_remake_plan]]

## AI rules

- Prep tools (`vip`, `spx`, `excel`) must **not** route to stamp — only `stamper` tool calls `run_unified_stamp`.
- Packing-list edit from review: free-text item line, not variant presets.
- Packer qty: read `N Gallon` / `N Liter` from **product title**, not Shopee `Quantity:` when title embeds size.
