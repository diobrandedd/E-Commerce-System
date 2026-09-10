---
date: 2026-09-10
type: fix
---

# Fix: Scan-only Add to list; honey compare by variant+size

1. Make `batch_no` optional on scan/update; unique stickers may keep barcode batch as metadata.
2. Honey variance lines aggregate system + counted by `variant|size`.
3. Remove Add-to-list batch field; 500ms inter-scan cooldown; success popup with item name.
