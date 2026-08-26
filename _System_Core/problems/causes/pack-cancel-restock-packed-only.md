---
date: 2026-08-26
type: cause
area: deliveries-cancelled-restock
tags: [cause]
---

# Cause: Pack cancel restock only restored packed units to finished

`pack_cancel_restock_is_restorable` accepted `packed` (and later shipped) but not POS `sold`. Confirm always incremented packaging/finished aggregates and never credited RTS sellable by unit `pool`.
