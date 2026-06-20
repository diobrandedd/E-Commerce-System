# Problem record: Interactive page tour pilot (Deliveries review)

**Date:** 2026-06-17  
**Area:** SPA / UX / Deliveries

## Problem

Staff needed more than static guide text — a click-by-click walkthrough that highlights real controls on the page.

## Cause

Existing guide modals are read-only docs with no in-context highlighting.

## Fix

- Added `driver.js` + shared `runPageTour` / `pyxTour.css`
- `DeliveriesReviewGuideModal` with **Start tour** in footer
- `deliveriesReviewTour.ts` — 7 steps (intro, filters, table, photo column, actions, pagination, related pages)
- `data-tour` attributes on `DeliveriesReviewPage`
- Pattern doc: [[fixes/page-interactive-tour]]

## Files

- `app/src/app/components/tour/runPageTour.ts`
- `app/src/app/components/tour/pyxTour.css`
- `app/src/app/components/fulfillment/DeliveriesReviewGuideModal.tsx`
- `app/src/app/components/fulfillment/deliveriesReviewTour.ts`
- `app/src/app/pages/deliveries/DeliveriesReviewPage.tsx`
- `app/package.json` (driver.js)
- `_System_Core/005_SPA_UX_Patterns.md`
- `_System_Core/problems/fixes/page-interactive-tour.md`

## Verify

1. Open Deliveries review as prod_manager or executive
2. Tap **Guide** → read modal → **Start tour**
3. Confirm overlay, highlighted regions, Back/Next/Done, step counter
4. Test on phone width — popover should not clip off-screen
