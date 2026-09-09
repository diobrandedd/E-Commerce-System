---
target: "Raw Materials UI: Honey Warehouse, Production, Packaging, and Dried Paragis on desktop and mobile"
total_score: 23
max_score: 40
na_heuristics: 
p0_count: 1
p1_count: 3
target_identity: "file:C:\\xampp\\htdocs\\rts\\app\\src\\app\\pages\\honey"
timestamp: 2026-09-09T07-58-03Z
slug: app-src-app-pages-honey
closed: true
---
# Raw Materials UI Critique

Method: dual-agent (A: bb2304c6-40df-4e2f-8013-33419a41a26b · B: 4a90dfb7-9a8a-4a86-aefc-0b12690230b3)

## Design Health Score

| # | Heuristic | Score | Key issue |
|---|---|---:|---|
| 1 | Visibility of system status | 2/4 | Production and Packaging can fail without a clear user-facing error state. |
| 2 | Match system / real world | 3/4 | Strong domain language; Paragis path conflicts with its guide. |
| 3 | User control and freedom | 2/4 | Recovery and correction paths vary across pages. |
| 4 | Consistency and standards | 2/4 | RawDept has mobile cards; Honey remains table-first. |
| 5 | Error prevention | 2/4 | Competing actions weaken guardrails. |
| 6 | Recognition rather than recall | 3/4 | Stages are visible; authoritative actions are not always clear. |
| 7 | Flexibility and efficiency | 3/4 | Barcode flows are fast; mobile tables slow floor work. |
| 8 | Aesthetic and minimalist design | 2/4 | Equal-weight actions flatten hierarchy. |
| 9 | Error recovery | 1/4 | Some failures look like valid empty or stale data. |
| 10 | Help and documentation | 3/4 | Useful guides, but one documented flow is not visibly exposed. |
| **Total** | | **23/40** | **Acceptable — significant improvements needed** |

## Design Specificity Verdict

The interface is more product-specific than a generic admin dashboard. Raw bags, tea bags (WIP), pouch yield, honey stages, batch traceability, and barcode workflows clearly belong to this operation. The weakness is operational hierarchy: Warehouse, Production, Packaging, and Dried Paragis frequently present many actions with nearly equal emphasis.

The deterministic detector returned 0 findings across the five target files. It missed substantive source issues: click-only rows, unlabeled search, unnamed dialogs, small touch targets, silent failures, and desktop tables on mobile.

No browser overlay was available because browser automation is not exposed. Evidence came from independent source assessments, responsive-class inspection, and the project UI rules.

## Overall Impression

The foundation is credible and calm. Dried Paragis is especially strong at expressing real production concepts. The biggest opportunity is to turn each page into a clear floor workflow: one dominant next action, one compact secondary-actions surface, and a mobile-native inventory view.

## What's Working

- Clear Honey stage orientation across Warehouse, Production, and Packaging.
- Paragis reflects the physical process through raw bags, WIP tea bags, pouch estimates, deduction previews, and waste paths.
- DM Sans, cream surfaces, gold accents, restrained borders, shared modals, and status badges fit a calm operational product.

## Priority Issues

### P0 — The visible Paragis workflow conflicts with its guide

The guide describes a Pouch step, but the page does not expose an obvious authoritative Pouch action and passes `onProduce` as a no-op. Make Stock in raw bags → Use raw bags → Pouch the dominant flow and move waste, barcode, and administrative actions into an Actions sheet.

Suggested command: `$impeccable clarify`

### P1 — Honey mobile pages are still desktop tables

Warehouse and Production rely on horizontally overflowing multi-column tables; Packaging enforces a 36rem minimum width. Use stacked mobile cards below `sm`, preserving tables only for larger screens.

Suggested command: `$impeccable adapt`

### P1 — Accessibility semantics are incomplete

Packaging rows are clickable `<tr>` elements without keyboard activation or focusability. Trace search has placeholder-only naming. Some Production dialogs lack programmatic titles, and shared modal focus management is incomplete. Use semantic controls, explicit labels, named dialogs, focus management, and 44px mobile targets.

Suggested command: `$impeccable audit`

### P1 — Failure states can look like valid inventory

Production ignores load failures, Packaging lacks a clear catch/error state, and Warehouse mixes animated errors with static red text. Standardize `AppErrorBanner`, retry, stale-data copy, and preserved input.

Suggested command: `$impeccable harden`

### P2 — Action hierarchy is overloaded

Dried Paragis can expose roughly eight similarly weighted actions. Packaging mixes traceability, bottle stock-in, leak/damage, reports, and Honey Comb administration. Give every stage one primary action, one secondary action, and an Actions modal for the rest.

Suggested command: `$impeccable distill`

## Persona Red Flags

- Alex, power user: the authoritative Paragis completion path is unclear, making speed risky.
- Sam, accessibility-dependent: click-only rows, placeholder-only search, unnamed dialogs, static errors, and small controls obstruct use.
- Casey, distracted mobile user: horizontal Honey tables, top-heavy actions, dense supporting text, and small controls are poor for one-handed floor work.

## Minor Observations

- Honey stage navigation is recognizable but likely shorter than the preferred 44px touch height.
- RawDept mobile activity cards are the strongest responsive pattern and should become the Honey model.
- Packaging trace search is visually secondary despite its operational importance.
- Honey Comb administration appears coupled to Packaging for implementation convenience.
- Frequent 10px labels are polished on desktop but fragile on warehouse phones.

## Questions to Consider

1. Should the first improvement secure the Paragis production path or make all Honey stages mobile-native?
2. Should the interface optimize for one-handed floor work or dense desktop oversight first?
3. Should reports and Honey Comb administration move out of the daily Packaging workflow?
