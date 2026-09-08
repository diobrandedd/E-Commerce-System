---
type: fix
tags: [rts, barcode, inventory, session, atomic]
---

# Fix: RTS stock-in review session

RTS sellable stock-in uses a server-backed draft session:

1. Start or resume the current user's draft.
2. Scan continuously; SKU presentations add `+1`, while a unique sticker appears once.
3. Review the mobile cards/table, edit SKU quantities, or remove mistakes.
4. Cancel to discard without inventory changes, or confirm to revalidate and apply every line in one transaction.

`BarcodeCameraScannerModal` keeps its existing cooldown by default. RTS opts into `presentationEdge`: one decode is accepted while a code remains visible, and the same code can be accepted again as soon as it leaves the frame and is presented again. Each accepted presentation carries a request key so network retries are idempotent.

Run `sql/migration_phase76_rts_stock_in_sessions.sql` before using the feature.
