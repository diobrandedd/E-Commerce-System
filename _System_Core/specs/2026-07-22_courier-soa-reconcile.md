---
status: approved
created: 2026-07-22
owner: ops
---

# Feature: Courier SOA reconcile

> Theory — compare courier statement-of-account Excel to our shipped parcels.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[plans/archive/2026-07-22_courier-soa-reconcile_plan]]

## Problem

Ops download J&T and SPX SOA exports and need to know whether every billed tracking number was actually shipped by us, and whether every parcel we marked shipped for a date range appears on the courier SOA. Partner sold list only checks partner order numbers one way.

## Users & roles

Same as Online Parcels / Partner sold list: executive, prod_manager, staff, csr_tl, csr_staff.

## Desired outcome (success criteria)

- [ ] Upload J&T or SPX SOA `.xlsx` and auto-detect format from headers
- [ ] Select ship date from/to; compare only our shipped Online Parcels for that courier in range
- [ ] See matched, missing-from-SOA, and extra-in-SOA counts and previews (no amounts)
- [ ] Download a copy of the SOA with tracking cells green (matched) or red (not in our set)

## Behavior (business rules)

1. Date range filters **only our** `ship_date` on shipped Online Parcels; the SOA file is used in full.
2. Match key is tracking / waybill number (normalized).
3. Courier filter follows detected SOA format (`jnt` or `spx`) via courier label bucketing.
4. Highlight paints the uploaded sheet’s tracking column only; parcels we shipped that are absent from the file appear in the UI list only.

## User flow (high level)

1. Open Courier SOA under Order management.
2. Pick ship date from/to, upload SOA Excel, run reconcile.
3. Review stats and mismatch tables; download highlighted Excel.

## Data & integrity (conceptual)

Shipped online parcels vs courier SOA tracking set. No schema change. Session stash/download tokens only.

## UI/UX intent

- Same feel as Partner sold list (`PageLayout`, FileZone, motion feedback, Guide modal).
- No COD/freight amounts shown.

## Out of scope

- Flash / other couriers
- Filtering SOA rows by courier date columns
- Amounts / billing audit
- Multi-sheet report workbook (download is highlighted SOA only)

## Open questions

_None — approved in chat 2026-07-22._
