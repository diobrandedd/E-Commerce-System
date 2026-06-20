---
type: cause
tags: [cause, auth, api]
---

# Cause: Parcel API gate checked SPA role names not IMS session roles

## What went wrong

`online_parcels_user_can_access()` listed `csr_tl` / `csr_staff`, but session stores IMS `tl` / `staff`. SPA normalizes on login only — API returned 403 for real CSR users.

## Where in code

- `includes/online_order_parcels.php`

## Related problems

- [[records/2026-06-05_online-parcels-csr-forbidden]]

## Prevent

Mirror `waybill_stamper_user_can_access()` — accept `tl`/`staff` + CSR dept lookup.
