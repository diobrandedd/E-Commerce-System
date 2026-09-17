---
date: 2026-09-17
type: problem-record
status: fixed
area: honey-packaging
tags: [problem]
---

# Problem: Packaging page showed live on-hand instead of stock-in report

## Symptom

After variant pools shipped, Raw Honey → Packaging cards showed live finished bottles on hand. Ops already use Products → Honey for that, and expected Packaging to answer “how many bottles were scanned into inventory on this date?” for reporting.

## Cause

→ [[causes/honey-packaging-on-hand-on-stage-page]]

## Fix

→ [[fixes/honey-packaging-stocked-in-report-ui]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `app/src/app/pages/honey/HoneyPackagingPage.tsx` |
| Products link | `HoneyProductInventoryPage.tsx`, `HoneyBottledInventoryPanel.tsx` |
| Tests | `tests/inventory/raw-materials.spec.ts`, `honey-lot-progress.spec.ts` |

## Deploy

- SPA build + hard refresh only (no migration)

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- Related: [[problems/records/2026-09-17_honey-packaging-variant-pools]]
