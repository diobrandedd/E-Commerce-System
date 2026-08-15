# Problem: Page header actions overflowed off-screen on mobile

## Problem
On phones, header action buttons (Guide / Request barcodes / Scan to inventory / Generate + print) ran past the right edge and were clipped. Worst on Raw Honey production, which puts every action in the header.

## Cause
`PageLayout` header was `flex items-center justify-between` with the actions column marked `shrink-0`. With no wrapping allowed, a wide action set could not shrink or reflow, and the parent column has `overflow-hidden`, so the buttons were simply cut off.

## Fix
- Header container is now `flex-wrap` with CSS `order`: title (1), bell (2), actions (3).
- Below `lg` the actions block takes `w-full`, dropping to its own row and wrapping normally; at `lg` it returns inline between title and bell (`lg:order-2 lg:w-auto`).
- Actions are only rendered when present, so pages without actions gain no extra row.
- Title/subtitle now `truncate` inside `min-w-0` so a long title cannot push the bell out.
- Raw Honey header labels shorten on small screens ("Request", "Scan", "Print"); full labels return at `sm`.

`RawDeptMaterialPage` already hid desktop actions on mobile in favour of its inline "Do next" card, and is unaffected.

## Files
- `app/src/app/components/PageLayout.tsx`
- `app/src/app/pages/honey/HoneyProductionPage.tsx`
