---
type: cause
tags: [cause, raw-materials, mobile, guidance]
---

# Cause: Raw materials desktop-table and guidance drift

## What went wrong

The three Raw Honey stage pages retained desktop tables as their only list presentation, while the newer RawDept page had already adopted phone cards. Separately, the Paragis guide continued to describe legacy Pouch accounting after barcode stock-in became an independent finished-inventory event.

## Where in code

- `HoneyWarehousePage.tsx`, `HoneyProductionPage.tsx`, `HoneyPackagingPage.tsx`
- `ParagisProductionGuideModal.tsx` and `paragisProductionTour.ts`
- `RawDeptMaterialPage.tsx`

## Related problems

- [[records/2026-09-09_raw-materials-ui-fix]]

## Prevent

Every inventory table needs a 360 px card fallback, and guides must state whether an action mutates stock or records information only.
