---
type: cause
tags: [rts, barcode, inventory, session]
---

# Cause: RTS scans mutated inventory immediately

The RTS stock-in page posted every accepted scan directly to `stock_in_scan`. SKU scans immediately incremented the RTS rollup, while unique stickers immediately changed unit pool/status. The user therefore had no draft list where quantities or mistaken scans could be corrected before inventory changed.

The shared camera also used a 2.5-second same-code cooldown. Removing that timer without replacement would accept the same visible barcode every decode cycle, so intentional repeat scans and accidental repeated frames could not be distinguished.
