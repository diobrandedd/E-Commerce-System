---
type: cause
tags: [cause, spa, mobile, ux]
---

# Cause: Raw materials page packed too many header actions for phone width

## What went wrong

`RawDeptMaterialPage` rendered Finished link + Guide + multiple mutate actions directly in `PageLayout` header actions. On ~360px screens, title and actions competed for one row, causing overlap/wrapping and poor tap targets.

## Where in code

- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `app/src/app/components/PageLayout.tsx` (single-row header layout)

## Related problems

- [[records/2026-06-16_raw-dept-mobile-layout]]

## Prevent

Treat phone width as primary: keep only compact controls in header and move dense action sets into a modal/sheet. See [[005_SPA_UX_Patterns#Dept raw production (Paragis / BRC / Jar)]].
