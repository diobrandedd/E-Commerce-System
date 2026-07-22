---
date: 2026-07-22
type: cause
status: documented
area: fulfillment-historical-import
tags: [cause]
---

# Cause: No historical shipped import path

Production parcel create (`online_parcels_promote_from_batch`) always inserts `status = packing` with `created_at = NOW` and a required unique tracking number. VIP ORDER LIST Excels lack a TN column and represent already-sold months outside the live stamp→ship flow. Using promote + ship would invent packing state and risk inventory deduct.
