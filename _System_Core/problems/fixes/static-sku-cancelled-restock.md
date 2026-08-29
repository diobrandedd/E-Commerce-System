---
type: fix
area: fulfillment
tags: [cancelled-restock, sku, inventory]
---

# Fix: Quantity-based static SKU cancelled restock

Cancelled Restock now resolves configured SKU barcodes before the unique-sticker
fallback. The operator enters a whole-number quantity and chooses a context-aware
destination pool: Honey uses Packaging or RTS, while department products use Finished
or RTS. Entries are queued in a dedicated session-item ledger and credited only when
the session is confirmed, inside the existing transaction.

Honey Packaging returns use one reserved, system-managed unassigned packaging row per
variant. This satisfies the legacy packaging foreign key without asking for a batch
number or attributing the cancellation to a production batch. Static entries remain
quantity-based; no fake per-unit sticker rows are created. History exposes the SKU,
quantity, destination, scanner, scan time, and confirmation actor.

## Rollout

1. Run `sql/migration_phase72_sku_cancel_restock.sql` after phases 61 and 69 and verify
   `pack_cancel_restock_sku_items` exists and `honey_packaging_rows.production_row_id`
   accepts NULL.
2. Deploy the PHP/API and SPA changes, then hard-refresh the SPA.
3. In staging, test one Honey Packaging return, Honey RTS return, each department
   product through Finished and RTS, repeated static scans, mixed static/unique scans,
   discarded sessions, and unique-sticker regressions.
