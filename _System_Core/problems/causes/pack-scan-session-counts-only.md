---
type: cause
tags: [cause]
---

# Cause: Pack scan session showed counts only

## What went wrong

Live pack scan grouped stickers by product qty. Barcodes existed on `session.items` but were not on those rows. There was no API to delete a draft session line, so a wrong scan required discarding the whole session.

## Where in code

- `PackScanLivePanel` in `HoneyPackScanPage.tsx`
- Pack APIs ended at scan/confirm/discard — no per-item remove

## Related problems

- [[records/2026-08-19_pack-scan-session-barcode-remove]]

## Prevent

Draft session lines are the only safe delete. Confirmed pack history stays read-only.
