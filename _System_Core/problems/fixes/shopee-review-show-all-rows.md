---
type: fix
tags: [fix, shopee-spx]
---

# Fix: Show full batch in ShopeeItemsReviewPanel

## What we changed

Review step shows **all rows** sorted by page with item count, **Original** vs **Final item**, green highlight only when edited, scrollable table with sticky header. **Edit** still returns to carousel for that page.

## Implementation

- Removed `shopeeRowNeedsItemResolution` filter — use full `rows` sorted by `page`
- Renamed column **From PDF** → **Original**
- Added `max-h` scroll + sticky `thead`

## Verify

1. Import Shopee packing list + PDFs
2. Complete variant picks → **Review all items**
3. Table shows every page/tracking line, not only edited rows
4. **Confirm all & build Excel** still requires `allResolved`

## Related problems

- [[records/2026-06-05_shopee-spx-review-full-list]]
