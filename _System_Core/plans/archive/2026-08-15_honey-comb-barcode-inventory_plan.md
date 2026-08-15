---
status: archived
spec: "[[specs/2026-08-15_honey-comb-barcode-inventory]]"
created: 2026-08-15
approved_by: chat
---

# Implementation Plan: Honey Comb Barcode Inventory

> Spec: [[specs/2026-08-15_honey-comb-barcode-inventory]]

## Summary

Extend `dept_pouch_*` with `product_key=honey_comb`, place Request / Scan / Generate + own request list below Raw Honey Packaging, and retire Produce (410). Scan adds finished jars only; no BOM deduct.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| Includes | `DEPT_POUCH_BARCODE_KEYS` + jar unit for `honey_comb` |
| API | `dept_comb_produce` → 410 |
| DB | none (phase58 `product_key` already covers it) |
| SPA | `HoneyPackagingPage` section; hide Produce on raw comb page; guide copy |

## Implementation steps

### Phase 1 — Backend
1. Enable `honey_comb` in PHP + TS key lists; jar terminology
2. Gate `dept_comb_produce` with 410

### Phase 2 — SPA
1. Honey Comb section on Packaging page (modals + list)
2. Retire Produce UI; update guide to Packaging path

### Phase 3 — Docs / verify
1. Memory bank + problem record + regenerate maps
2. verify.ps1; archive this plan

## Done when

- Request with custom batch → generate → print → scan N → finished Honey Comb +N
- Produce retired; Packaging page owns the Honey Comb barcode workflow
- Universal verify resolves Honey Comb stickers

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-08-15
