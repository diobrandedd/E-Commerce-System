---
status: approved
created: 2026-08-21
owner: ops
area: rts
tags: [rts, barcodes, honey, dept, labels]
---

# RTS multi-batch barcode labels

> Theory — unlabeled RTS units get stickers, then stock-in via existing inventory scan.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[plans/active/2026-08-21_rts-barcode-labels_plan]]

## Problem

Some RTS honey bottles and dept pouches/jars have no barcodes. Staff cannot scan them into inventory. Printing one lot at a time wastes paper when each lot is only 2–10 units across many batch numbers.

## Users & roles

Staff with **`rts.barcode_labels`** (and executives). After stickers are applied, any user who can already stock-in honey/dept barcodes scans them on the existing inventory UIs.

## Desired outcome (success criteria)

- [ ] Enter many lines (product, batch no, bottling date, qty) and generate as **one job**
- [ ] Print **one continuous sheet** with a banner before each lot; next lot continues on the same page
- [ ] Stickers scan via normal stock-in and **increment** inventory
- [ ] Batches are tagged **`rts_label`** so origin is visible in RTS history

## Behavior (business rules)

1. Products: honey (all sizes + variant) and Paragis / BRC / Cacao / Honey Comb.
2. Batch number and bottling date are **manual** per line.
3. Generate creates one barcode batch per line inside one RTS label job (single transaction).
4. Print packs banners + labels in one A4 grid stream — no page break per batch.
5. Stock-in behaves like request-sourced barcodes (qty +1), not backfill.
6. Generate does **not** put units into RTS sellable/damaged pools — stick then scan only.

## User flow (high level)

1. Open RTS → Barcode labels
2. Add multiple rows → Generate all
3. Print combined sheet → stick → scan stock-in

## Data & integrity (conceptual)

- RTS label **job** with ordered **lines**
- Honey batches reuse custom packaging rows (batch no + bottling date + variant)
- Dept batches store user bottling date as `batch_date`
- Provenance: batch `source = rts_label` + job id

## UI/UX intent

- PageLayout under RTS; draft table; Generate all; Print; job history
- Feedback: success/error banners + pressable buttons

## Out of scope

- Auto RTS-pool assignment on generate/scan
- Camera workflow changes
- Unreadable-sticker fallback on Record RTS

## Approval to plan

- [x] Spec reviewed by human (implement request 2026-08-21)
- [x] Ready for implementation
