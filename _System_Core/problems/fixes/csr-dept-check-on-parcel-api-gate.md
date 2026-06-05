---
type: fix
tags: [fix, auth, api]
---

# Fix: CSR dept check on parcel API access gate

## What we changed

Added `online_parcels_user_is_csr_dept()`; allow `tl`/`staff` when `departments.name = 'csr'`. Keeps executive, prod_manager, normalized csr_* names.

## Verify

CSR TL opens `/fulfillment/online-parcels` → table loads, no Forbidden.

## Related problems

- [[records/2026-06-05_online-parcels-csr-forbidden]]
