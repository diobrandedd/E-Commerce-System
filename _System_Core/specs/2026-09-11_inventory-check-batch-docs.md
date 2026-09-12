# Spec: Inventory check required batch + bottling docs

**Status:** shipped  
**Date:** 2026-09-11

## Intent

Every inventory-check scan must record **batch number** and **bottling date** for documentation. Matching vs system inventory stays by product / variant / size counts only.

## Behavior

1. **Unique stickers** (honey bottles, Paragis / BRC / cacao / comb pouches): scan → auto-save using batch + bottling/batch date already on the sticker → camera stays open with a short “Scan complete” cue for continuous scanning.
2. **SKU barcodes**: scan → enter batch + bottling date (+ qty) → confirm line (camera closes for the form).
3. Compare / finalize / history / merged executive compare show product variance plus expandable **batch documentation** groups (one batch → many bottling dates / variants / sizes).
4. Same UX for owners and `inventory.check.view_all` / executive.

## Out of scope

Changing stock; matching by batch or date.
