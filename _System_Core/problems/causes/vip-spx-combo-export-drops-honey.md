---
date: 2026-06-19
type: cause
area: csr-order-remarks
tags: [cause, vip, spx, combo, export]
---

# Cause: Combo export kept dept line only when honey variant incomplete

## What happened

1. `interpretRawText()` parsed **both** honey + dept (e.g. 1L + Jar Cacao) into `builderLines`.
2. `composePackerLine()` used **`validLines`** that **filtered out** honey rows without a valid variant.
3. `finalRemarkForRow()` returned that partial `packerLine` for ambiguous rows.
4. `isRowFullyResolved()` treated rows as done when a **false** variant was auto-detected (see [[causes/vip-spx-size-letter-false-variant]]).

## Symptom

- Source: `1 x 1 L HONEY: 1 JAR CACAO` (Mildred Tamayo, SPX row 9).
- Export column AG: **`1 Jar Cacao`** only.

## Rule for agents

- **Never drop honey lines** from packer output — show `(pick variant)` for incomplete honey.
- **Multi-item combos** (`builderLines.length > 1`) require **`resolution.finalRemark`** from ItemLineBuilder save before export.
- `needsMainVariantPick()` must check **honey products only** (`isHoneyBuilderProduct` + `builderLineIsValid`), not dept SKUs like `jar_cacao`.

## Related

- [[fixes/vip-spx-order-remarks-parser-guards]]
- [[records/2026-06-19_vip-spx-parser-pitfalls]]
