---
date: 2026-08-15
type: problem-record
status: fixed
area: honey-barcodes
tags: [problem, honey, barcodes, packaging]
---

# Problem: "Batch already exists" when requesting a second size on the same batch

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

A user had requested labels for `HG072426` (light, 1L). Coming back to request 350ml on the
same batch, they used **+ Create custom batch / supplier…**, typed `HG072426` again, and got
`Batch HG072426 (light) already exists — pick it from the dropdown instead.` The request was
rejected and the form had to be re-done. Users read this as "I can't reuse a batch code",
which contradicts the intended workflow.

## Cause

→ [[causes/custom-packaging-treated-as-create-only]]

Size lives on the **label request**, not on `honey_packaging_rows` — one packaging row covers
1L / 350ml / 250ml / gallon. But `honey_bottle_create_custom_packaging()` was create-only: any
`(batch_no, variant)` hit threw. Every legitimate follow-up (new size, recount after a miscount)
looks identical to a duplicate at that layer.

## Fix

→ [[fixes/custom-packaging-reuse-existing-row]]

`honey_bottle_create_custom_packaging()` now returns the existing row instead of throwing, and
reports it through a `bool &$reused` out-param. Applies to both entry paths:

- typed batch code — exact `(batch_no, variant)` match is reused
- supplier + date — derived supplier code matching an existing `(batch_no, variant)` is reused
  rather than minting `JR063026-2` behind the user's back

`honey_unique_packaging_batch_no()` still appends `-2`, `-3` for genuinely distinct collisions.
Variant scoping from phase59 is unchanged: same code on another variant creates a new row.

Verified locally — same code + same variant returns one row id; same code + different variant
creates a second.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` — `honey_bottle_create_custom_packaging()` |
| API | `api/v1/bottle_barcodes.php` — returns `reused`; `created` is now `!$reused` |
| SPA | `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` — reuse-aware success text + hint |
| SPA | `app/src/app/lib/bottleBarcodes.ts` — `reused: boolean` on the response |
| Related | [[generated/modules/honey_bottle_barcodes]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh

## Links

- **Prior record:** [[records/2026-08-15_honey-batch-label-per-variant]] — the `(batch_no, variant)` unique key this builds on

## AI rules (optional)

Packaging rows are per `(batch_no, variant)` and size-agnostic. Do not add a uniqueness check
that includes size, and do not re-introduce a hard failure when a custom batch already exists —
reuse it.
