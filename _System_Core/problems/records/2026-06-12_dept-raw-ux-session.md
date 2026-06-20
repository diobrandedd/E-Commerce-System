---
date: 2026-06-12
type: problem-record
status: shipped
area: dept-raw-production, spa-layout
tags: [paragis, brc, jar_cacao, wip, guide, pagelayout]
---

# Session: Dept raw UX — centered layout, Paragis bags-opened, production guides

> Rollup of chat-driven work on raw materials production and page shell. Use this record when extending dept production or raw page UX.

## What shipped

### 1. Centered page content (global)

- **Problem:** Main content hugged left edge beside sidebar.
- **Fix:** [[fixes/page-layout-centered-content]] — `PageLayout` `contentWidth` (`default` max-w-6xl, `wide`, `full`); header + main share `mx-auto`.
- **Golden rule:** [[005_SPA_UX_Patterns]] #9 · `.cursor/rules/spa-ui-design.mdc`

### 2. Paragis — use raw bags (no tea-bag counting)

- **Problem:** Staff cannot count thousands of 2 g tea bags per run.
- **Fix:** [[fixes/paragis-use-raw-bags]] — `dept_pt_use_raw_bags`; input = 1 kg bags opened; WIP += `bags × 500`.
- **Spec:** [[specs/2026-06-12_paragis-production-wip]] (action #1 primary; `make_tea_bags` advanced only).
- **Record:** [[records/2026-06-12_paragis-use-raw-bags]]

### 3. Production guides (all dept raw pages)

- **Problem:** Inline “How Paragis production works” + yield boxes cluttered the page; BRC/Cacao had no guide.
- **Fix:** [[fixes/dept-raw-material-guides]]
  - `ParagisProductionGuideModal.tsx`
  - `DeptRawProduceGuideModal.tsx` (`brc` | `jar_cacao`)
- **Pattern:** [[fixes/page-guide-button-modal]]

## Files (primary)

| Area | Path |
|------|------|
| Layout | `app/src/app/components/PageLayout.tsx` |
| Paragis API | `includes/dept_production.php` · `api/v1/honey.php` |
| Raw page | `app/src/app/pages/inventory/RawDeptMaterialPage.tsx` |
| Client API | `app/src/app/lib/deptRawInventory.ts` |
| Guides | `app/src/app/components/inventory/ParagisProductionGuideModal.tsx` · `DeptRawProduceGuideModal.tsx` |

## Manual E2E (still open)

- [ ] Paragis: stock in 20 bags → **Use raw bags: 14** → raw 6, WIP ~7000 → **Pouch: 10** → packaging deduct
- [ ] BRC: stock in → **Produce** pouches → finished up, raw deduct preview matches
- [ ] Jar Cacao: stock in → **Produce** jars → same
- [ ] Hard refresh after `npm run build`

## Next agent checklist

1. Read [[specs/2026-06-12_paragis-production-wip]] before changing PT production.
2. New raw dept help → **Guide modal**, not inline panel.
3. PT floor input = **bags opened**; pouch count = finished; WIP waste for reconciliation.
4. BRC/Jar = single **Produce** at sell unit; no WIP table.
5. All `PageLayout` pages: no redundant `max-w-*` on page root ([[fixes/page-layout-centered-content]]).

## Links

- **Fragile:** [[003_Fragile_Edges#Dept catalog products (PT / BRC / Cacao)]]
- **Patterns:** [[005_SPA_UX_Patterns#Dept raw production (Paragis / BRC / Jar)]]
- **State:** [[001_System_State]]
