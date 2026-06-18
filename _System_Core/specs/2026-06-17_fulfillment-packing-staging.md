---
status: approved
created: 2026-06-17
area: fulfillment-packaging
tags: [packaging, staging, parcel, on_hold, re-waybill, roles]
---

# Fulfillment packing staging + on-hold re-waybill

Supplements [[specs/2026-06-17_fulfillment-packaging-recipes]].

## Summary

Two-bucket model for **fulfillment packaging** (bubble, cushion, pouches, foam, cardboard):

1. **Warehouse** (`stocks.on_hand`) — prod_manager stock-in from deliveries
2. **For packing staging** — prod_manager issues materials to the packing area; ship scan consumes staging only

**On-hold + re-waybill:** sealed box, wrong label → no inventory revert → new waybill over old → PM re-waybill in Deliveries → ship on new TN (idempotent deduct).

**Courier cancelled:** order dead at courier, box still sealed → scan on Deliveries → Cancelled (no inventory revert) → same re-waybill + ship path as on-hold.

**Opened returns:** RTS module (out of scope).

## Roles

| Role | Warehouse packaging | Staging | On-hold parcels |
|------|---------------------|---------|-----------------|
| HR | Update invoice price only | — | — |
| prod_manager | Stock-in, deduct for use | Issue, view, counters | Re-waybill + ship |
| csr_tl | — | — | View only |
| executive | All | All | All |

## Staging data

- `fulfillment_packing_balances` — `issued_qty`, `consumed_qty` per fulfillment SKU
- `fulfillment_packing_issues` + `_issue_lines` — take-out log with parcel capacity estimate
- Display: `consumed / issued`, remaining = `issued - consumed`

## Ship deduct

`parcel_fulfillment_apply_deduct` increments `consumed_qty` on staging (not `stocks.on_hand`). Block when `issued - consumed < recipe qty`.

Finished goods (honey) still deduct via `honey_parcel_apply_deduct` unchanged.

## On-hold flow

1. PM **Mark on hold** — status `on_hold`, no revert, preserve `deducted_at`
2. CSR prints replacement waybill (Online Orders)
3. PM **Re-waybill** — old TN → new TN, same `parcel_id`, history row (allowed from `on_hold` or `cancelled`)
4. **Ship** on new TN — skip deduct if already done

## Courier-cancelled flow

1. PM scans parcel on **Deliveries → Cancelled** — status `cancelled`, clear ship metadata, **no** `honey_parcel_revert_deduct`
2. CSR prints replacement waybill if needed
3. Same re-waybill + ship steps as on-hold

## API

- `fulfillment_packing.php`: `packing_balances_list`, `packing_issue_create`, `packing_issues_list`
- `deliveries.php`: `hold_parcel`, `rewaybill`
- `materials.php`: `packaging_price_lot` (HR), stock-in restricted to PM+exec

## Deploy

1. `sql/migration_phase34_fulfillment_packing_staging.sql`
2. `php tools/seed_fulfillment_packing_opening.php` (optional opening balances)
3. PM issues current physical packing stock before first ship

## Links

- [[specs/2026-06-17_fulfillment-packaging-recipes]]
- [[fixes/packaging-inventory-roles]]
