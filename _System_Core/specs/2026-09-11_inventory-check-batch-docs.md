# Spec: Inventory check required batch + bottling docs

**Status:** shipped  
**Date:** 2026-09-11

## Intent

Every inventory-check scan must record **batch number** and **bottling date** for documentation. Matching vs system inventory stays by product / variant / size counts only.

## Behavior

1. Flow: scan barcode → enter batch + bottling date (+ qty for SKUs) → confirm line.
2. Compare / finalize / history / merged executive compare show product variance plus expandable **batch documentation** groups (one batch → many bottling dates / variants / sizes).
3. Same UX for owners and `inventory.check.view_all` / executive.

## Out of scope

Changing stock; matching by batch or date.
