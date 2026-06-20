---
date: 2026-06-05
type: problem-record
status: fixed
area: csr-online-orders
tags: [problem, shopee-spx, packing-list]
---

# Problem: SPX packing list missed gallon qty and variant

## Symptom

Shopee packing list rows like `Product Name:1 Gallon … HONEY …; Variation Name:Amber; Quantity: 2` showed gallon in the snippet but not packer-ready text — variant carousel appeared even though `Variation Name` already had Amber.

## Cause

`shopee_spx_build_items_from_product_info()` only auto-resolved `1LITER-*` variations. Standalone `Light|Amber|Dark` in `Variation Name` with gallon in product title fell through to a truncated product-name fallback that still needed variant resolution.

## Fix

Gallon branch: read `Quantity` + `Variation Name` variant → `{qty} Gallon Honey {VARIANT}`. `shopee_spx_extract_honey_variant()` shared helper; SPA `honeyItemNeedsVariantResolution` recognizes complete gallon lines; preset picker maps `Amber Gallon` → qty-prefixed line.

**Follow-up:** `[1]…[2]…` multi-product cells split per block → e.g. `1 Liter Honey AMBER ; 1 Liter Honey DARK` (one tracking row, two items).

**Follow-up:** B2T1 `350mL` product title (no liter) → `3× 350ml (LAD)`; 1L B2T1 still → `3L-HONEY (LAD) + 350ml`.

**Follow-up:** Packer qty from product title (`1 Gallon` + `Quantity: 2` → `1 Gallon Honey AMBER`). Pre-stamp `stash_download`.

**Consolidated:** [[problems/records/2026-06-05_online-orders-prep-stamper-split]]

## Files

| Role | Path |
|------|------|
| Primary | `includes/shopee_spx_product_info.php` |
| SPA | `app/src/app/lib/honeyItemVariant.ts` |

## Deploy

`cd app && npm run build` + hard refresh
