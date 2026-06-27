---
date: 2026-06-19
type: fix
area: csr-order-remarks
tags: [fix, vip, spx, parser]
---

# Fix: VIP/SPX order remarks parser guards (2026-06-19 session)

> **Read before editing** `itemLineBuilder.ts`, `orderRemarksParse.ts`, or `honey_parcel_item_parser.php`.

## 1. Strip size before variant letters

- `stripHoneySizeTokens()` removes `1 L HONEY`, `2L-HONEY`, `250ml`, `350ml`, etc.
- Run **before** L/A/D letter heuristics in `detectVariants()` and `detectVariantsFromText()`.
- **Cause:** [[causes/vip-spx-size-letter-false-variant]]

## 2. Combo lines — never silent drop

- `composePackerLine()` includes **all** lines; incomplete honey → `1 x 1L Honey (pick variant)`.
- `isRowFullyResolved()`: invalid honey OR multi-item without `finalRemark` → **not done**.
- `finalRemarkForRow()`: ambiguous unresolved → return **raw**, not partial packer line.
- Split `HONEY:` combos: `1 x 1 L HONEY: 1 JAR CACAO` → two segments.
- **Cause:** [[causes/vip-spx-combo-export-drops-honey]]

## 3. 3L variant list includes LLA

- Added **LLA** everywhere 3L presets are defined.
- Explicit text: `2 lights and 1 amber` → LLA; `1 light and 2 amber` → LAA (do not conflate).
- Fixed `ALL LIGHT` on 3L returning `LL` → must be **`LLL`**.
- **Cause:** [[causes/vip-spx-3l-variant-list-incomplete]]

## 4. 250ml honey (same as 350ml rules)

- Product `250ml` in builder, parser, stock labels, PHP parcel parser.
- Detect **before** 350ml in `detectHoneySize()`.

## 5. CSR UI — long combo cards

- `ItemLineBuilder`: collapsible lines + scroll; Save/Cancel pinned.
- Order cards: `max-h` + scrollable body (`AmbiguousOrderCarousel`, `ItemResolverCarousel`).

## 6. J&T concatenated 3L + 350ml (no separator)

- Split `3L HONEY(ALL DARK)1 PC 350ML(DARK)` into two segments before parse.
- Parse `350ML(DARK)` → variant **D** (word in parens, not only `(D)`).
- **Cause:** [[causes/vip-spx-jnt-concatenated-350ml]]

## 7. Dept products (Paragis, Jar Cacao, BRC) — qty + CSR confirm

- VIP item text often uses `1 x 2 Paragis Tea` — leading `1 x` is **not** qty; use `extractDeptQty()` / `honey_parcel_extract_dept_qty()`.
- Also parse trailing `Paragis Tea x2`.
- **Never auto-submit** dept-only rows: `needsDeptConfirm` forces VIP carousel + `ItemLineBuilder` until CSR saves `finalRemark`.
- Honey `1 x 2L Honey` still uses `extractQty()` (pack count = 1).

## 8. Concatenated count + color (`1 amber 2dark`)

- `normalizeVariantCountText()` splits `2dark` → `2 dark` before variant detection.
- Explicit **1 amber + 2 dark** → **ADD** (check before single-color fallbacks).
- If both amber and dark appear but counts are ambiguous → return empty / null (CSR pick), **not** default AAA.
- **Cause:** `\bdark\b` does not match `2dark` (no word boundary between digit and color).

## Files (primary)

| File | Role |
|------|------|
| `app/src/app/lib/itemLineBuilder.ts` | Segment split, interpret, VALID_3L, 250ml, composePackerLine |
| `app/src/app/lib/orderRemarksParse.ts` | detectVariants, isRowFullyResolved, finalRemarkForRow, presets |
| `app/src/app/lib/honeyPackagingStock.ts` | 250ml stock in builder dropdown |
| `app/src/app/lib/honeyItemVariant.ts` | Shopee 250ml size hint |
| `includes/honey_parcel_item_parser.php` | 250ml + LLA backend parity |
| `app/src/app/components/csr/onlineOrders/ItemLineBuilder.tsx` | Collapsible accordion lines |
| `app/src/app/components/csr/AmbiguousOrderCarousel.tsx` | Scrollable card body |
| `app/src/app/components/csr/onlineOrders/ItemResolverCarousel.tsx` | Scrollable card body |

## Test strings (manual)

```
1 x 1 L HONEY: 1 JAR CACAO          → builder: 1L (pick) + Jar Cacao; block export until save
1 x 1L Honey (L) + 1 Jar Cacao      → auto export OK
textarea: 2 lights and 1 amber + 3L  → LLA valid, no Pick variant
textarea_input_1: 1 amber 2dark + Set B 3L → ADD (not AAA)
1 x 250ml Honey (A)                 → parses 250ml
3L HONEY(ALL DARK)1 PC 350ML(DARK)  → 1 x 3L Honey (DDD) + 1× 350ml (D)
```

## Spec

[[specs/2026-06-19_vip-spx-order-interpretation]]
