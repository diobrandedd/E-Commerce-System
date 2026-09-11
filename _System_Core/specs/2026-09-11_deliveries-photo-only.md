# Spec: Deliveries photo-only (no CSR parcel / no deduct)

**Status:** approved — implementing  
**Date:** 2026-09-11

## Problem

Add Deliveries currently blocks when no CSR `online_order_parcels` row exists (“Finalize CSR batch first”). Warehouse needs a **before-shipment photo** keyed by tracking so RTS can show it later — without depending on CSR finalize, without viewing parcel contents, and without any inventory/packaging deduct.

## Goal

Deliveries **Ship / Add deliveries** becomes a **documentary station**:

1. Scan (or type) tracking number.
2. Capture / upload before-shipment photo.
3. Save to `delivery_batch_items` for that TN (and business date batch).
4. Done — ready for next scan.

No CSR parcel required. No finished-goods or fulfillment/packaging deduct — **even if** a CSR parcel exists for that TN. No parcel contents UI.

## RTS behavior (unchanged, confirmed)

RTS before-shipment image continues to resolve primarily by **tracking number** on `delivery_batch_items` (`rts_prefill_delivery_lookup` / case enrich). Photo-only saves are enough for that.

## Non-goals

- Changing how CSR creates parcels or packing lists.
- Changing On hold / Re-waybill / Cancelled flows (still parcel-based) except copy if it claims ship deducts stock.
- Deducting or reverting inventory from Deliveries Ship.
- Requiring parcel line items / contents preview on Add deliveries.

## Rules

| Case | Behavior |
|------|----------|
| TN unknown to CSR | Allow photo save; `online_parcel_id` null |
| CSR parcel exists | Allow photo save; may link `online_parcel_id` if present; **do not** deduct stock; **do not** require showing contents |
| Duplicate photo same day / already recorded | Keep existing duplicate warning UX (or soft allow with confirm) — product: warn, don’t hard-block forever |
| Ship API / parcel status | Prefer **not** marking parcel `shipped` from this flow (documentary only). Status changes stay with other parcel tools if needed |

## UX

- Subtitle / CTAs: photo documentation language (not “ship deducts packing staging”).
- Flow: scan → camera/file → Save photo → toast → next scan.
- Remove dependency on parcel preview / “Finalize CSR first” error for Ship tab.
- Hide parcel contents / line items from this path.

## Permissions

Unchanged: Deliveries roles (executive / prod_manager per existing gate).

## Acceptance

- [ ] Can save a before-shipment photo for a TN with no CSR parcel.
- [ ] Can save when CSR parcel exists without any inventory/packaging change.
- [ ] RTS prefill/enrich finds that photo by the same TN.
- [ ] UI does not require or display parcel contents for Add deliveries.
- [ ] Fragile edges + memory bank updated (Deliveries ≠ stock deduct).
