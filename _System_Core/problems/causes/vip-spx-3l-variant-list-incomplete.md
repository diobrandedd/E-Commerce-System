---
date: 2026-06-19
type: cause
area: csr-order-remarks
tags: [cause, vip, 3L, variant]
---

# Cause: 3L code LLA parsed but not in allowed list

## What happened

`detectVariantsFromText()` correctly mapped **`2 lights and 1 amber`** → **`LLA`** (Light · Light · Amber).

But `VALID_3L` / `build3LPresets()` / builder dropdown listed **LAA** (1 light · 2 amber) and **not LLA**. `builderLineIsValid()` rejected LLA → **"Pick variant"** badge even when the collapsed line showed `LLA`.

## Symptom

- Set B order: `textarea_input_1: 2 lights and 1 amber` + `3L-HONEY:/ Color:Mix`.
- Line 1 displayed **`3L Honey · LLA · x1`** with yellow **Pick variant**.
- Dropdown had LAA but **no LLA**.

## Complete 3L combo codes (keep in sync)

| Code | Meaning |
|------|---------|
| LAD | 1 each Light, Amber, Dark |
| LLL | all Light |
| AAA | all Amber |
| DDD | all Dark |
| **LLA** | **2 Light + 1 Amber** |
| LAA | 1 Light + 2 Amber |
| LLD | 2 Light + 1 Dark |
| ADD | 1 Amber + 2 Dark |
| AAD | 2 Amber + 1 Dark |

**Sync in:** `itemLineBuilder.ts` (`VALID_3L`, `variantOptionsForProduct`), `orderRemarksParse.ts` (`VALID_3L`, `build3LPresets`), `includes/honey_parcel_item_parser.php` (`HONEY_PARCEL_VALID_3L`).

## Rule for agents

When adding a new 3L mix from CSR language, update **all three** lists + test `"2 lights and 1 amber"` resolves without Pick variant.

## Related

- [[fixes/vip-spx-order-remarks-parser-guards]]
- [[records/2026-06-19_vip-spx-parser-pitfalls]]
