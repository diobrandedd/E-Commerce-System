---
date: 2026-08-15
type: problem-record
status: fixed
area: honey-barcodes
tags: [problem, honey, barcodes, traceability, dates]
---

# Problem: Verified sticker showed a months-old batch/bottling date

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Labels generated on Aug 15 verified as **batch / bottling date 2026-07-11**. Separately, a
custom batch created with the Bottling date field set to today stored a different date entirely.
Two unrelated paths, same visible symptom: the date on the sticker did not match the day the
bottles were actually filled.

## Cause

→ [[causes/batch-date-inherited-not-stamped]]

Two independent sources of drift:

1. **Lot path** — `honey_bottle_generate_batch()` copied `batch_date` from the packaging row's
   `bottling_date`. That row's date is set once, when the lot first enters packaging. Bottling
   from one lot across several weeks meant every sticker run inherited the first day.
2. **Custom path** — a trailing MMDDYY in the typed batch code silently overrode the picked
   Bottling date (`HG072426` forced `2026-07-24`). The generator's inline form even disabled
   the date input once a code was typed, so the override was invisible.

## Fix

→ [[fixes/stamp-batch-date-at-generation]]

- `honey_bottle_generate_batch()` stamps `date('Y-m-d')`. Each generation run is its own
  bottling day; the packaging row's `bottling_date` is left untouched so lot history is intact
  and previously generated batches keep their recorded dates.
- `honey_bottle_create_custom_packaging()` no longer parses MMDDYY out of the code — the
  Bottling date field is authoritative. The code's trailing digits are just part of the human
  label now.
- Generator inline form: date input stays enabled when a code is typed; labelled "Bottling date".

Verified locally: custom batch with code ending `072426` + today picked stored today; generating
against a packaging row dated `2026-05-25` produced `batch_date` of today; verify lookup agreed.

**Forward-only** — batches generated before this change keep their inherited dates.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` — `honey_bottle_generate_batch()`, `honey_bottle_create_custom_packaging()` |
| SPA | `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` — date field enabled + hint copy |
| Related | [[generated/modules/honey_bottle_barcodes]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh

## Links

- **Related record:** [[records/2026-08-15_honey-custom-batch-reuse]]

## AI rules (optional)

Dept pouches (`dept_pouch_generate_for_request`) still derive `batch_date` from a trailing MMDDYY
in `batch_no`, defaulting to today — that flow has no date field, so the parse is its only date
signal. Do not "unify" the two without adding a date input to the dept request form first.
