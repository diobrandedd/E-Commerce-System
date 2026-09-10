---
type: cause
tags: [cause, pack-scan, sku, session]
---

# Cause: Pack scan SKU lines lacked set-qty

SKU (non-unique) pack session rows already store an aggregatable `qty`, and scans can increment it, but the SPA only showed a static total and delete. Operators had to rescanning or remove lines to correct a wrong bulk quantity. Unique stickers correctly remain one-per-row.
