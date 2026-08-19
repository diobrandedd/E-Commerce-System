---
type: fix
tags: [fix]
---

# Fix: Expand pack session rows to barcodes; remove draft scans

## What we changed

Each live session product row expands to its barcodes. Trash removes one draft scan. Stock is unchanged until Confirm.

## Implementation

- `honey_bottle_pack_session_remove_item()` — DELETE from honey or dept session items if status is `draft`
- API `pack_remove_item`
- Session rows use chevron expand; History confirmed sessions stay read-only

## Verify

1. Pack scan → scan two stickers of the same product.
2. Tap the product row — barcodes list.
3. Trash one barcode — qty drops; that code can be scanned again.
4. Confirm still deducts remaining units only.

## Related problems

- [[records/2026-08-19_pack-scan-session-barcode-remove]]
