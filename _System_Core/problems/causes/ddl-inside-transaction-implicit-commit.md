---
type: cause
tags: [cause, deliveries, database]
---

# Cause: DDL inside delivery upload transaction caused implicit commit

## What went wrong

`deliveries_upload_photos()` began a transaction, then called `online_parcels_ensure_schema()` (`CREATE TABLE IF NOT EXISTS`). MySQL DDL implicitly commits, ending the txn; final `commit()` threw "no active transaction".

## Where in code

- `includes/deliveries_upload.php` — upload + parcel link path

## Related problems

- [[records/2026-06-05_deliveries-upload-transaction]]

## Prevent

Run schema ensure / DDL **before** `beginTransaction()`.
