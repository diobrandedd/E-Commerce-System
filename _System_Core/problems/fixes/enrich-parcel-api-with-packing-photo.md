---
type: fix
tags: [fix, parcels, deliveries]
---

# Fix: Enrich parcel API with packing_photo from deliveries

## What we changed

`online_parcels_find_packing_photo()` + batch enrich on list/get/lookup. `OnlineParcelDetailModal` shows photo or "Not yet packed".

## Related problems

- [[records/2026-06-05_online-parcels-packing-photo]]
