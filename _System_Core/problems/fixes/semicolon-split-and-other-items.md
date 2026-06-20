---
type: fix
tags: [fix, parcels, parser]
---

# Fix: Split item lines on semicolon; collect other_items

## What we changed

Split `Item/s` on `;` and `+`; parse segments independently. Non-honey products go to `other_items` (noted, no honey deduct). Modal shows **Other products** section.

## Verify

Mixed order line parses correct honey qty/variant; Paragis etc. appear in other_items only.

## Related problems

- [[records/2026-06-05_parcel-item-parser-semicolon]]
