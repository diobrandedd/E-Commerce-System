---
status: approved
created: 2026-09-09
owner: ops
area: inventory-ui
tags: [raw-materials, honey, paragis, mobile, accessibility, playwright]
---

# Raw materials UI fix

> Clarify the current inventory-only Paragis barcode workflow and make the Raw Honey stages safe and usable on production phones.

Links: [[010_Product_Theory]] · [[005_SPA_UX_Patterns]] · [[plans/active/2026-09-09_raw-materials-ui-fix_plan]]

## Problem

- Dried Paragis guidance promises a Pouch operation that deducts WIP and packaging, but the shipped barcode stock-in intentionally credits finished inventory without those deductions.
- Raw Honey Warehouse, Production, and Packaging use dense desktop tables on phones.
- Some row actions, searches, dialogs, and errors do not expose complete accessible semantics or recovery.

## Locked behavior

1. Paragis barcode stock-in remains an **inventory-only event**.
2. Stock-in must work without deducting raw bags, WIP tea bags, or packaging.
3. Daily production records remain informational and do not change stock.
4. This change does not alter backend inventory accounting or role permissions.

## Success criteria

- [ ] Paragis page and guide state the inventory-only behavior plainly and do not promise automatic Pouch deductions.
- [ ] Honey Warehouse, Production, and Packaging show stacked cards below `sm` and preserve desktop tables at `sm+`.
- [ ] Mobile primary controls are at least 44 px high and pages have no horizontal document overflow at 360 px.
- [ ] Packaging scan details use semantic buttons; trace search and edited dialogs have programmatic names.
- [ ] Load and mutation failures use standard animated error feedback with a retry where appropriate.
- [ ] Playwright navigates Paragis and all three Honey routes at desktop and 360 × 800 mobile viewports.

## Approval

- [x] Behavior confirmed in chat: inventory-only Paragis stock-in, no WIP/raw/packaging deduction (2026-09-09)
- [x] Implementation plan approved by user (2026-09-09)
