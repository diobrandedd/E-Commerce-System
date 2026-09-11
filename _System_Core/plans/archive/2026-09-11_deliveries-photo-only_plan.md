---
status: shipped
spec: "[[specs/2026-09-11_deliveries-photo-only]]"
created: 2026-09-11
approved_by: user (chat 2026-09-11)
---

# Implementation Plan: Deliveries photo-only documentary flow

> Spec: [[specs/2026-09-11_deliveries-photo-only]]

## Summary

Make Deliveries **Add deliveries (Ship tab)** save tracking + before-shipment photo only. Drop CSR parcel requirement and any ship/inventory deduct messaging or calls. RTS keeps TN-based photo lookup.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]]
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]] · feedback animations
- Compress photo client-side; upload at capture (existing pattern)

## Impact preview

| Area | Change |
|------|--------|
| API | `parcel_by_tracking` / Ship tab: allow null parcel; new or reuse upload-only confirm (no `ship_parcel` deduct path) |
| Includes | Stop using `online_parcels_ship` from documentary Ship flow; optional link `online_parcel_id` on upload when TN matches |
| DB | None expected |
| SPA | `DeliveriesPage.tsx` — scan → photo → save; remove CSR gate + contents; copy |
| Tests | Playwright: photo save without parcel |

`impact.php` before editing shared ship/upload helpers.

## Implementation steps

### Phase 1 — Backend

1. [ ] Confirm upload already inserts `delivery_batch_items` without parcel; keep/ensure TN + optional `online_parcel_id` link.
2. [ ] Documentary finish endpoint **or** SPA-only: after upload success, do **not** call `ship_parcel` / do not mark shipped / do not deduct.
3. [ ] Soften `parcel_by_tracking` consumers for Ship tab (parcel optional).

### Phase 2 — SPA

1. [ ] Remove “Finalize CSR batch first” hard stop on Ship tab.
2. [ ] Remove parcel contents preview from Add deliveries path.
3. [ ] Confirm CTA = save photo; success copy about documentation / RTS, not inventory.
4. [ ] Update page subtitle and Guide text if present.
5. [ ] Leave On hold / Re-waybill / Cancelled as parcel-based unless they break without ship status.

### Phase 3 — Docs / verify

1. [ ] Update [[003_Fragile_Edges]] Deliveries section (photo ≠ deduct).
2. [ ] Problem record + [[002_Memory_Bank]]; regenerate maps if API/includes change.
3. [ ] `tools/verify.ps1`; Playwright smoke for no-parcel photo save.

## Test plan

- [ ] Unknown TN → photo saves; RTS prefill shows image.
- [ ] Known CSR parcel TN → photo saves; stock/staging unchanged.
- [ ] Duplicate recording warning still sensible.
- [ ] On hold / re-waybill still work for real parcels.

## Done when

Acceptance checklist in the spec is checked; plan → `plans/archive/`.
