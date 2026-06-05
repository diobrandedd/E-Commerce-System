---
type: fix
tags: [fix, shopee-spx, tracking]
---

# Fix: Narrow OCR confusables to Flash/Lazada P-IDs only

## What we changed

Exclude Shopee prefixes `PH`, `SPEPH`, `SPX` from O→0. Apply confusables only to Flash/Lazada marketplace IDs matching `^P[A-Z0-9]{11,17}$`. Mirrored in PHP and Python.

## Implementation

- Early return when tracking starts with `PH|SPEPH|SPX`
- Tighten regex from `^P[A-Z0-9]+$` to `^P[A-Z0-9]{11,17}$` for O→0

## Verify

1. Re-run Shopee SPX packing list import
2. Master list download row 2 ends with **O** (`PH26239152010O`)
3. Flash stamp regression: `P3623KKO3MYAD` still matches `P3623KK03MYAD`

## Related problems

- [[records/2026-06-05_shopee-spx-tracking-o0]]
