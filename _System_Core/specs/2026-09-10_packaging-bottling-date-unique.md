---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [honey, packaging, bottling-date, stock-in, uniqueness, filter]
---

# Packaging lots unique by bottling date

> When finished honey is scanned into inventory, the **current calendar date** becomes that lot’s `bottling_date`. The same `batch_no` + `variant` may exist more than once if the bottling dates differ. Packaging UI can filter by bottling date.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[005_SPA_UX_Patterns]] · [[plans/archive/2026-09-10_packaging-bottling-date-unique_plan]]

## Problem

- Today `honey_packaging_rows` is unique on `(batch_no, variant)` only (phase 59).
- Stock-in / create paths look up by batch+variant and **reuse** the first row, keeping the **original** `bottling_date`.
- Scan-into-inventory writes `scanned_at` but does **not** create a new dated lot for “today.”
- Packaging’s main list filter is **scan date** (`scanned_at` report), not bottling date on lot rows — so operators cannot isolate “JR12345 · dark bottled 2026-09-10” vs the same batch bottled another day.

## Locked behavior (from chat 2026-09-10)

1. **Uniqueness:** one packaging row per `(batch_no, variant, bottling_date)`.
2. **Stock-in / first create for a day:** if no row exists for that batch+variant+**today**, insert one with `bottling_date = CURDATE()` (app server local date, same convention as existing `date('Y-m-d')` stock paths). If it exists, **add qty to that row only**.
3. **Same batch later day:** e.g. `JR12345` dark stocked again tomorrow creates/uses a **new** row with tomorrow’s date — not a rewrite of yesterday’s row.
4. **Filtering:** Packaging (and related lot lists) must support filtering by **bottling date**, while keeping **scan-date** history where it already exists (both filters).
5. **No silent rewrite:** later scans must not change an existing row’s `bottling_date`.

## Scope

### In scope (v1)

- Honey finished packaging lots (`honey_packaging_rows`) and every stock-in / ensure / custom-batch path that currently collapses by `(batch_no, variant)`.
- Packaging SPA: bottling-date filter + clear display of bottling date on lot/scan rows.
- FIFO / pack / POS continue to prefer older `bottling_date` when multiple rows share variant+size (already ordered by bottling date).

### Explicitly out of scope / follow-on

- **Dept finished aggregates** (Paragis / BRC / Jar Cacao / Honey Comb `dept_inventory_rows`) remain **one pool per product_key** today. Unique stickers already carry `batch_no` / `created_date`; they do not get a second packaging-style dated lot table in v1 unless a separate spec says so.
- Backfill that invents fake historical scan days for already-merged qty (optional cleanup plan only — default leave existing rows as-is with their current `bottling_date`).

## Success criteria

- [ ] Unique key on `honey_packaging_rows` is `(batch_no, variant, bottling_date)`.
- [ ] Stock-in on day D for batch B + variant V credits only the row with `bottling_date = D` (create if missing).
- [ ] Two days of stock-in for the same B+V produce two packaging rows; neither overwrites the other’s date.
- [ ] Packaging UI can filter lot-oriented views by bottling date (and still filter scan history by scan date).
- [ ] Pack scan / POS / pack deduct still work when multiple dated rows exist for the same variant+size.
- [ ] Playwright or manual E2E covers: create lot day 1 → stock again day 2 → filter each date.

## Non-goals

- Changing how `scanned_at` is recorded.
- Making sticker `batch_date` (label print run) the same as packaging `bottling_date` automatically.
- Auto-splitting historically merged quantities by guessed scan days without an ops-approved cleanup script.

## Approval

- [ ] Behavior confirmed: unique by bottling date; stock-in day = bottling date for that lot
- [ ] Filters: bottling date + keep scan date
- [ ] Implementation plan approved in chat
- Approved date: _
