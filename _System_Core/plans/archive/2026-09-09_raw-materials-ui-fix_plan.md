---
status: shipped
spec: "[[specs/2026-09-09_raw-materials-ui-fix]]"
created: 2026-09-09
approved_by: user
completed: 2026-09-09
---

# Implementation Plan: Raw materials UI fix

## Summary

Clarified the inventory-only Dried Paragis barcode flow, added phone-native cards to all three Raw Honey stages, hardened accessibility and error recovery, and validated the routes with Playwright.

## Constraints

- Paragis barcode stock-in continues to credit finished inventory without deducting raw bags, WIP, or packaging.
- Backend accounting, route gates, and role-specific actions are unchanged.
- UI follows [[004_SPA_Design_System]] and [[005_SPA_UX_Patterns]].

## Implementation

1. [x] Align Dried Paragis guide and action copy with inventory-only barcode stock-in.
2. [x] Add stacked mobile cards to Honey Warehouse, Production, and Packaging while retaining desktop tables.
3. [x] Replace click-only rows, label search/dialogs, raise phone targets to 44 px, and standardize errors.
4. [x] Add authenticated Playwright coverage for desktop and 360 × 800 mobile routes.
5. [x] Run detector, verifier, Playwright, build/lints, and memory-bank updates.

## Verification

- Impeccable detector: 0 findings.
- Playwright Chromium + 360 × 800 mobile: 8 passed.
- Repository verifier: PHP syntax and SPA production build passed.

## Approval

- [x] Human approved in chat on 2026-09-09.
