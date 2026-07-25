---
date: 2026-07-22
type: fix
status: shipped
---

# Fix: Courier SOA Excel reconcile

- Upload one or more J&T/SPX SOA files → auto-detect each format → merge tracking numbers → compare to shipped Online Parcels (`ship_date` range + courier buckets for every format in the upload set)
- Mix J&T + SPX in one run; our shipped set includes all detected couriers
- UI: matched / missing-from-SOA / extra-in-SOA (no amounts); per-file download buttons
- Download: copy of each SOA with tracking cells green (matched) or red (extra)

API: `courier_soa_reconcile.php` actions `stash` | `reconcile` | `download`. Route `/fulfillment/courier-soa-reconcile`.
