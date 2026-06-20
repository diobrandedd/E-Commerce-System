# Centered page content (PageLayout)

## Problem

Main content hugged the left edge of the viewport beside the sidebar, leaving a large empty band on the right — especially visible on Requests and hub pages.

## Fix

`PageLayout` wraps **header** and **main** children in a shared centered column:

```text
w-full mx-auto px-4 sm:px-6 max-w-6xl   (default)
w-full mx-auto px-4 sm:px-6 max-w-7xl   (contentWidth="wide")
w-full mx-auto px-4 sm:px-6 max-w-none  (contentWidth="full" — rare wide grids)
```

- **Default:** `contentWidth="default"` (`max-w-6xl`) — use for all new exec/inventory/CSR pages.
- **Wide tables:** `contentWidth="wide"` — Product Costing, dense data grids.
- **Full bleed:** `contentWidth="full"` only when a page truly needs the full main column width.

## Do not

- Add page-level `max-w-* mx-auto` on body content inside `PageLayout` (layout owns width).
- Use `max-w-5xl` / `max-w-4xl` on the page root without `mx-auto` — content will sit left inside the column.
- Narrow forms/hubs: optional inner `max-w-* mx-auto` is fine for intentionally compact blocks.

## Files

- `app/src/app/components/PageLayout.tsx` — `contentWidth` prop + `contentChromeClass`
- `_System_Core/004_SPA_Design_System.md` — layout shell
- `_System_Core/005_SPA_UX_Patterns.md` — golden rule #9
- `.cursor/rules/spa-ui-design.mdc`
