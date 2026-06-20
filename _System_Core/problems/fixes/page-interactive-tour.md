# Page interactive tour (driver.js)

## Pattern

Guide modal = reference docs. **Start tour** = dimmed overlay + spotlight on real UI + arrow tooltip (driver.js).

**Practice tour** (complex pages only): **Start practice tour** = click-to-advance hands-on walkthrough with fixture UI — no API calls, uploads, or mutations. Uses `tourClickToContinue` + optional `onAdvance` in `tourStepHelpers.ts`. Shared `PracticeTap` button component.

## Files

| File | Role |
|------|------|
| `app/src/app/components/tour/runPageTour.ts` | Shared runner + modal-close delay |
| `app/src/app/components/tour/pyxTour.css` | Honey-gold popover theme |
| `app/src/app/components/fulfillment/deliveriesReviewTour.ts` | Pilot step definitions |
| `app/src/app/components/tour/guides/onlineOrdersPracticeTour.ts` | Online Orders E2E practice + overview tours |
| `app/src/app/components/tour/GuideTourFooter.tsx` | Shared Start tour / Close footer |
| `app/src/app/components/fulfillment/DeliveriesReviewGuideModal.tsx` | Guide + Start tour footer |

## Adding a tour to another page

1. Add `data-tour="feature-step-id"` on stable elements (prefer section wrappers and column headers over row buttons).
2. Create `featureTour.ts` with `DriveStep[]` and export `runFeatureTour()`.
3. Add **Start tour** to the page's `*GuideModal` footer; wire `onStartTour` on the page.
4. Document in [[005_SPA_UX_Patterns#Interactive page tour (optional pilot)]].

## Pitfalls

- Hidden responsive duplicates — `querySelector` hits the first DOM node; use section-level targets or a resolver function for `element`.
- Empty tables — do not target row-only buttons; use column headers + copy.
- Modal z-index — close guide modal before `runPageTour`; tour overlay uses driver default (above page).

## Motion feedback

`pyxTour.css` + `runPageTour.ts` hooks:

- Popover slide/scale on open and each step (`pyx-tour-popover-step-enter`)
- Title/description stagger on step change
- Spotlight breathing glow + brief land pulse on highlighted target
- Arrow nudge toward target; Next button hover shadow + press scale
- Progress counter tick on step change
- `prefers-reduced-motion: reduce` disables decorative motion; driver stage animation off
