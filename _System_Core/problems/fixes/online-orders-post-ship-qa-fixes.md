---
type: fix
tags: [fix, online-orders]
---

# Fix: Online Orders post-ship QA fix bundle

## What we changed

| Issue | Fix |
|-------|-----|
| Partner run_partner 500 | `online_orders_coord()` + getCell/setCellValue (PhpSpreadsheet 2) |
| Shopee extract upload | `extractPdfToExcel(pdfFiles)` not stash tokens |
| Partner stamp download | `StampResultPanel` — no auto-navigate before download |
| Shopee 1L HONEY no variant | `items_variant` in extractor + `honeyItemVariant.ts` carousel |
| VIP Step 1 upload | Shared `FileZone` drag/drop |

## Related problems

- [[records/2026-06-04_online-orders-post-ship-qa]]
