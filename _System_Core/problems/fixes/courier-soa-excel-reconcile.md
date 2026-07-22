---
date: 2026-07-22
type: fix
status: shipped
---

# Fix: Courier SOA Excel reconcile

- Upload J&T/SPX SOA → auto-detect format → compare to shipped Online Parcels (`ship_date` range + `rts_courier_label_bucket`)
- UI: matched / missing-from-SOA / extra-in-SOA (no amounts)
- Download: copy of SOA with tracking cells green (matched) or red (extra)

API: `courier_soa_reconcile.php` actions `stash` | `reconcile` | `download`. Route `/fulfillment/courier-soa-reconcile`.
