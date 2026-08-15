---
date: 2026-08-15
type: problem-record
status: fixed
area: barcodes-printing
tags: [problem, honey, dept-pouch, barcodes, printing, css]
---

# Problem: Last row of a printed label page vanished and overlapped on the next page

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Printing a multi-page barcode sheet (honey bottles or dept pouches): **row 17 of page 1 was
missing**, and its labels reappeared stacked on top of row 1 of page 2 — two barcodes rendered
inside one cut-guide box, both unscannable. On screen the sheet looked correct; only the printed
output (and print-to-PDF) was wrong. Single-page batches were unaffected, which is why it went
unnoticed until a batch exceeded 68 labels.

## Cause

→ [[causes/fractional-grid-rows-fragment-across-pages]]

The sheet was `display: grid` with `grid-template-rows: repeat(17, 1fr)` inside a box pinned to
`height: 287mm` (A4 297mm minus the 5mm `@page` margins — zero slack).

Two compounding faults:

1. **`1fr` rows have no intrinsic height.** When a grid fragments across a page boundary the
   engine re-solves the track sizes for the second fragment, so cells land on top of each other.
2. **`287mm` left no tolerance.** Any sub-millimetre rounding, or a printer with slightly larger
   unprintable margins, pushed the final row past the page box — which is what started the
   fragmentation in the first place.

Reproduced independently: the same file printed on Letter paper produced a clipped bottom and a
fully blank second page.

## Fix

→ [[fixes/fixed-height-label-rows]]

Rows are now a fixed physical height instead of fractional:

- `.sheet` uses `grid-auto-rows: 16.8mm`; the `height: 287mm` pin is gone (also removed from the
  `@media print` block and `.page`'s `min-height`).
- `.label` carries `height: 16.8mm` to match, keeping `break-inside: avoid`.
- 17 × 16.8mm = **285.6mm**, ~1.4mm under the 287mm printable height, so a page can no longer
  spill a phantom row.

Layout is visually unchanged — 4 × 17 = 68 labels per A4, same ~40mm symbol width and cut guides.

Verified with a 150-label batch rendered through headless Chrome: 3 pages split 68 / 68 / 14,
page 1 carries a full 17 rows, and page 2 opens with a clean row of four distinct codes.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` — `honey_bottle_label_sheet_html()` print CSS |
| Primary | `includes/dept_pouch_barcodes.php` — `dept_pouch_label_sheet_html()`, identical CSS |
| Related | [[generated/modules/honey_bottle_barcodes]], [[generated/modules/dept_pouch_barcodes]] |

## Deploy

- PHP only — no SPA rebuild needed. Re-open the print sheet (hard refresh if cached).

## Links

- **Fragile edges:** print sheets are server-rendered HTML; they bypass the SPA entirely, so SPA
  builds never surface a regression here.

## AI rules (optional)

Never size print-grid rows with `1fr`, and never pin a printable box to the exact page height.
Use fixed `mm` row heights that total slightly under the printable area. Both label sheets share
this CSS — fix them together.
