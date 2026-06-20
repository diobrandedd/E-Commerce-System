---
date: 2026-06-11
type: problem-record
status: fixed
area: costing
tags: [problem, costing, spa, ux]
---

# Problem: Product Costing page required 67% zoom — cluttered setup panels

> Wide flex rows + honey setup + stock prices table pushed content off-screen.

## Symptom

Executive had to zoom out to see product names and metrics together. Honey costing setup and stock prices occupied vertical space; metric columns floated far right with empty middle gap.

## Cause

- `flex-1` on product name stretched rows horizontally.
- Collapsible honey guide + full stock table duplicated info available elsewhere (Supplier Master, Packaging Materials).
- No single help surface for BOM math across honey + dept products.

## Fix

→ [[fixes/costing-page-layout-and-guide]]

- Removed honey setup + stock prices sections.
- **Guide** button + `CostingGuideModal`.
- Table-style **grid rows** + column headers (`CostingRowColumnHeaders`, `costingRowGridClass`).
- Viewport-bound products scroll; `PageLayout` `overflow-x-hidden`.

## Files

| Role | Path |
|------|------|
| Fix pattern | [[fixes/costing-page-layout-and-guide]] |
| Page | `app/src/app/pages/exec/CostingPage.tsx` |
| Components | `CostingProductMetrics.tsx`, `CostingGuideModal.tsx`, `costingRowLayout.ts` |
| Shell | `app/src/app/components/PageLayout.tsx` |

## Links

- [[problems/records/2026-06-11_costing-session-rollup]]
- [[fixes/costing-page-layout-and-guide]]
- [[005_SPA_UX_Patterns#Product Costing page]]
