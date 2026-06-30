---
status: approved
created: 2026-06-27
area: sales, honey-inventory
tags: [bulk-orders, warehouse, costing, executive]
---

# Bulk honey orders (warehouse B2B)

## Goal

Executive-only **Bulk orders** tab: record B2B honey deals sold from **warehouse gallons**, pick **supplier codes** manually (multi-lot), negotiate sale price from **COGS floor**, then **Confirm & deduct** warehouse stock when shipment leaves.

## Non-goals (MVP)

- Online Parcels / Deliveries / waybill
- Prod_manager access or COGS visibility
- AR / PDF export

## Workflow

1. Create draft: customer, sale lines (variant + qty + negotiated price), warehouse allocations per line
2. Live costing: COGS, revenue, margin; negotiation helper (margin % | ₱/gal | total ₱)
3. Save draft — warehouse unchanged
4. Confirm & deduct — FIFO-validated deduct per allocation row
5. Void confirmed — restore warehouse qty

## Acceptance

- 100 gal amber from two supplier codes → COGS shown → 25% margin → quote filled → draft saved
- Confirm deducts warehouse; void restores
- Unpriced warehouse row blocks save/confirm
- Insufficient stock blocks confirm
