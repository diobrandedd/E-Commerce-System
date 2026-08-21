---
status: approved
spec: "[[specs/2026-08-21_rts-barcode-labels]]"
created: 2026-08-21
approved_by: user (chat implement 2026-08-21)
---

# Implementation Plan: RTS multi-batch barcode labels

> Spec: [[specs/2026-08-21_rts-barcode-labels]] · **Shipped 2026-08-21**

## Summary

Add RTS Barcode labels page + API to batch-generate honey and dept barcodes with manual batch/date, continuous combined print, and `rts_label` provenance. Stock-in stays on existing scan UIs.

## Implementation steps

### Phase 1 — Data / backend

1. [x] Spec + this plan
2. [x] Migration phase66
3. [x] Honey/dept generate hooks for `rts_label`
4. [x] `rts_barcode_labels` include + API + continuous print

### Phase 2 — Frontend

1. [x] Permission keys, route, sidebar
2. [x] Page + API client

### Phase 3 — Integration

1. [x] Verify loop
2. [x] Memory bank + problem record
3. [x] Archive this plan
