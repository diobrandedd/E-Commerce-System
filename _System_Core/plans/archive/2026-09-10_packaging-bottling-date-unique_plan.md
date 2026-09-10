---
status: shipped
spec: "[[specs/2026-09-10_packaging-bottling-date-unique]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Packaging lots unique by bottling date

> Spec: [[specs/2026-09-10_packaging-bottling-date-unique]]  
> **Do not implement until this plan is approved.**

## Summary

Change honey packaging uniqueness from `(batch_no, variant)` to `(batch_no, variant, bottling_date)`, make every stock-in/create path look up by **batch + variant + today** (or an explicitly chosen bottling date), and add Packaging UI filtering by bottling date while keeping the existing scan-date report filter.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]] — honey packaging FIFO, SKU stock-in, pack scan
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]
- Existing uniqueness migration: `sql/migration_phase59_honey_batch_no_per_variant.sql`

## Impact preview

| Area | Change |
|------|--------|
| DB | New migration: drop `uq_honey_pk_batch_variant`; add unique `(batch_no, variant, bottling_date)`; keep/index `bottling_date` |
| Includes | `honey_inventory.php`, `honey_bottle_barcodes.php` — all find/ensure/create packaging helpers |
| API | Honey / bottle barcode list & stock-in responses include unambiguous lot identity (`id` + `bottling_date`); optional `bottling_date` query param on packaging list endpoints |
| SPA | Packaging page (and any packaging lot picker) — bottling date filter; labels show batch · variant · date |
| Dept products | **No schema change in v1** — finished dept qty stays per `product_key` |

### Functions to impact-check before edit

- `honey_unique_packaging_batch_no`
- `honey_production_ensure_packaging_row`
- `honey_bottle_ensure_packaging_shell`
- `honey_bottle_create_custom_packaging`
- SKU / unique stock-in paths that `SELECT … WHERE batch_no = ? AND variant = ?`
- POS / pack FIFO already `ORDER BY bottling_date ASC` — verify they do not assume one row per batch

## Implementation steps

### Phase 1 — Schema

1. [ ] Add `sql/migration_phase77_honey_packaging_batch_variant_date.sql` (next free phase number — confirm against latest migration before shipping).
2. [ ] Drop unique key `uq_honey_pk_batch_variant`.
3. [ ] Add unique key e.g. `uq_honey_pk_batch_variant_date` on `(batch_no, variant, bottling_date)`.
4. [ ] Confirm no duplicate `(batch_no, variant, bottling_date)` exists before applying (pre-check query in migration comments).

### Phase 2 — Shared lookup helpers

1. [ ] Introduce a single helper, e.g. `honey_fetch_packaging_by_batch_variant_date($pdo, $batchNo, $variant, $bottlingDate)`, and `honey_ensure_packaging_row_for_stock_in(..., $bottlingDate = null)` defaulting to `date('Y-m-d')`.
2. [ ] Replace every `WHERE batch_no = ? AND variant = ? LIMIT 1` reuse with **batch + variant + date**.
3. [ ] Update `honey_unique_packaging_batch_no` so “unique batch code” means unique among **same variant + same bottling date** (not globally across all dates — same JR12345 may exist on other dates).
4. [ ] Custom batch create: lookup by typed code + variant + **picked** bottling date; do not reuse a different-date row.
5. [ ] Production ensure / bottling transfer: if creating from production, use **today** as bottling date for a new row; if an open production lot already linked to a packaging row, document whether link is 1:1 or 1:many (recommend: stock-in to **today’s** packaging row even if production was stocked out earlier — production_row_id may appear on multiple dated packaging rows).

### Phase 3 — Stock-in paths

1. [ ] Unique sticker stock-in → credit packaging row for sticker’s batch + variant + **today** (create shell if needed).
2. [ ] SKU stock-in (Production + Packaging panels) → same rule; if UI passes an explicit packaging_row_id / bottling date, respect that (hybrid pick); otherwise today.
3. [ ] Ensure comments that say “packaging row keeps original bottling_date” are rewritten to the new rule.
4. [ ] Confirm pack scan / sales log still store `packaging_row_id` so historical deductions stay tied to the correct dated lot.

### Phase 4 — Packaging SPA filters

1. [ ] Packaging **lot** views: add bottling-date filter (Today / pick date / All).
2. [ ] Keep existing **scan date** filter on “Scanned into inventory” report (`packaging_scan_report`).
3. [ ] Row labels: always show `batch_no · variant · bottling_date` where operators pick a lot.
4. [ ] Trace / search by batch still returns **all** dated rows for that batch (not only one).

### Phase 5 — Docs, verify, cleanup

1. [ ] Update [[003_Fragile_Edges]] and [[005_SPA_UX_Patterns]] Packaging section.
2. [ ] Problem record + [[002_Memory_Bank]].
3. [ ] `php tools/memory_bank/generate_all.php`.
4. [ ] Verifier + focused Playwright: two-day uniqueness + bottling filter (may use clock/date injection or API-seeded rows).
5. [ ] Archive this plan; set [[001_System_State]] to shipped.

## Files to touch (expected)

| File | Action |
|------|--------|
| `sql/migration_phase77_….sql` | create |
| `includes/honey_inventory.php` | modify — uniqueness + ensure/create |
| `includes/honey_bottle_barcodes.php` | modify — stock-in / custom batch / shell |
| `api/v1/honey.php` and/or `bottle_barcodes.php` | modify — list filters |
| `app/src/app/pages/honey/HoneyPackagingPage.tsx` | modify — bottling date filter |
| `app/src/app/lib/bottleBarcodes.ts` / honey clients | modify |
| `_System_Core/003_Fragile_Edges.md` | modify |
| `_System_Core/005_SPA_UX_Patterns.md` | modify |

## Test plan

- [ ] Day D1: stock-in JR12345 dark → one row `bottling_date = D1`.
- [ ] Same day again: qty increases on **same** row; date unchanged.
- [ ] Day D2 (or forced date in test): stock-in JR12345 dark → second row `bottling_date = D2`; D1 row unchanged.
- [ ] Filter bottling date = D1 → only D1 row; D2 → only D2.
- [ ] Scan-date filter still lists scans for that `scanned_at` day across lots.
- [ ] Custom batch with picked date creates/reuses only that date.
- [ ] Pack scan / POS can deduct from older dated lot first (FIFO).
- [ ] Duplicate insert same batch+variant+date fails uniquely (DB constraint).

## Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Existing code assumes one packaging row per batch+variant | Centralize lookup; grep all `batch_no = ? AND variant = ?` before ship |
| `honey_unique_packaging_batch_no` appends `-2` unnecessarily across dates | Scope uniqueness to date |
| Operators confuse scan date vs bottling date | UI labels: “Bottling date (lot)” vs “Scan date (stock-in)” |
| Historical merged lots stay on one old date | Accept in v1; optional ops cleanup later |
| Dept products expected to behave the same | Spec out of scope; confirm with user if a follow-on dated-lot table is required |

## Rollback

- Revert SPA/API/includes.
- Migration down: drop new unique key; restore `uq_honey_pk_batch_variant` only if no cross-date duplicates exist — otherwise leave new key and stop writing multi-date rows until fixed.

## Open questions (resolve before coding)

1. **Production link:** If one production gallon lot feeds bottling across two calendar days, should both packaging rows keep the same `production_row_id`? (Recommended: **yes**.)
2. **Explicit lot pick:** On Packaging custom / leftover SKU stock-in, if the operator selects an **older** dated lot from a picker, add to that row (hybrid) — confirm.
3. **Timezone:** Keep server `date('Y-m-d')` as today (current convention), or force Asia/Manila? (Recommended: document and keep current server TZ unless ops wants Manila locked.)

## Approval

- [ ] Human approved — OK to implement in Agent mode
- Approved date: _
