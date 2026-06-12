---
date: 2026-06-12
type: fix
area: dept-raw-production
tags: [guide, paragis, brc, jar_cacao, raw-materials, ux]
---

# Fix: Dept raw material pages — header Guide modals

## Rule

All three **Raw Materials** dept pages use the **Guide** header button → scrollable `AppModal`. **Never** inline “how production works” panels or yield callout boxes on the page body.

| Route | Modal | Content |
|-------|-------|---------|
| `/inventory/raw-materials/paragis` | `ParagisProductionGuideModal` | 3-stage WIP workflow · Use raw bags · Pouch · yield · waste · advanced exact tea-bag count |
| `/inventory/raw-materials/black-rice` | `DeptRawProduceGuideModal` (`brc`) | 2-stage · Stock in → Produce pouches · yield · bag waste |
| `/inventory/raw-materials/cacao` | `DeptRawProduceGuideModal` (`jar_cacao`) | 2-stage · Stock in → Produce jars · yield · bag waste |

## Implementation

- **Page:** `RawDeptMaterialPage.tsx` — `showGuide` for all three `productKey`s; `guideOpen` state; Guide button left of Stock in.
- **Yield text:** import from `deptStockInYieldHint()` inside modals only — not duplicated on page body.
- **Paragis advanced:** guide footer link “Open exact tea bag count…” → `make_tea_bags` modal (`dept_pt_make_tea_bags`).

## Related production UX (same page)

| Product | Primary production action | API |
|---------|---------------------------|-----|
| Paragis | **Use raw bags** (bags opened → derived WIP) | `dept_pt_use_raw_bags` |
| Paragis | Pouch | `dept_pt_pouch` |
| BRC / Jar | **Produce** (single step) | `dept_brc_produce` / `dept_jar_produce` |

See [[fixes/paragis-use-raw-bags]] · [[fixes/dept-production-wip-bom]].

## Do not

- Inline accordion / “How X works” card on `RawDeptMaterialPage`
- Default-open guides
- Link non-executive users to `/costing` in guide copy

## Docs

[[fixes/page-guide-button-modal]] · [[005_SPA_UX_Patterns#Dept raw production (Paragis / BRC / Jar)]] · [[005_SPA_UX_Patterns#Page guide button (mandatory)]]
