---
date: 2026-09-09
type: problem-record
status: fixed
area: inventory-ui
tags: [problem, raw-materials, honey, paragis, mobile, accessibility]
---

# Problem: Raw materials guidance and phone lists were inconsistent

## Symptom

Dried Paragis promised production deductions that the approved inventory-only barcode flow did not perform, while Raw Honey lists required desktop-table scrolling on production phones. Some errors, dialogs, searches, and row actions also lacked complete recovery or accessible semantics.

## Cause

→ [[causes/raw-materials-desktop-table-and-guidance-drift]]

## Fix

→ [[fixes/dept-raw-guidance-and-mobile-honey]]

## Files

- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `app/src/app/components/inventory/ParagisProductionGuideModal.tsx`
- `app/src/app/pages/honey/HoneyWarehousePage.tsx`
- `app/src/app/pages/honey/HoneyProductionPage.tsx`
- `app/src/app/pages/honey/HoneyPackagingPage.tsx`
- `app/src/app/components/AppModal.tsx`
- `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx`
- `app/src/app/components/dept/DeptPouchBarcodePanels.tsx`
- `app/src/app/lib/inventoryRoutes.ts`
- `tests/inventory/raw-materials.spec.ts`

## Deploy

- `cd app && npm run build`
- Hard refresh the deployed SPA.
- No migration or backend accounting change.

## Links

- **Fragile edges:** [[003_Fragile_Edges#Dept catalog products (PT / BRC / Cacao)]]
- **Patterns:** [[005_SPA_UX_Patterns#Raw Honey (three stages)]]
- **Plan:** [[plans/archive/2026-09-09_raw-materials-ui-fix_plan]]
- **Spec:** [[specs/2026-09-09_raw-materials-ui-fix]]

## AI rule

Do not make Paragis finished barcode stock-in depend on raw bags, WIP, or packaging without a new approved accounting spec.
