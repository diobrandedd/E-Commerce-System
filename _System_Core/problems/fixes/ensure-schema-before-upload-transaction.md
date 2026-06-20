---
type: fix
tags: [fix, deliveries, database]
---

# Fix: Ensure parcel schema before upload transaction

## What we changed

Call `online_parcels_ensure_schema()` before `beginTransaction()`. Link parcel with lightweight `SELECT id` inside txn (no DDL).

Also added review photo viewer: `photo_url` on `review_list`, thumbnail + modal on review page.

## Verify

1. Add deliveries → photo → Done → no transaction error
2. Review batches shows photo thumbnail and View modal

## Related problems

- [[records/2026-06-05_deliveries-upload-transaction]]
