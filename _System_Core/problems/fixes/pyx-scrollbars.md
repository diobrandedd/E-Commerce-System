---
date: 2026-06-12
type: fix
area: spa-ui
tags: [scrollbar, scroll, ux, motion]
---

# Fix: Pyx themed scrollbars (global rule)

## Mandatory

| Case | Class |
|------|--------|
| Default vertical scroll | `overflow-y-auto` (auto-themed light thumb) |
| Sidebar / dark panel | `pyx-scroll-dark` + `overflow-y-auto` |
| Opt out (native OS scrollbar) | `no-pyx-scroll` |
| Explicit wrapper | `<PyxScrollRegion variant="light\|dark" />` |

## Behavior

- Slim rounded thumb — no default Windows arrows/chrome
- Thumb **hidden** until hover or scroll (`.pyx-scroll-active` via `usePyxScrollDocument` in `Root.tsx`)
- Fade-in transition ~280ms

## Sidebar dropdown motion

`SidebarNavCollapse` + `SidebarNavChevron` + `SidebarNavToggleButton` (`sidebarNavMotion.tsx`) — height/opacity expand, chevron rotate, tap scale. Respects `useReducedMotion()`.

## Links

[[005_SPA_UX_Patterns#Pyx scrollbars (mandatory)]] · `app/src/styles/theme.css`
