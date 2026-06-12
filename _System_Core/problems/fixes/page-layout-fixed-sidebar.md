---
date: 2026-06-12
type: fix
area: spa-layout
tags: [pagelayout, sidebar, scroll, ux]
---

# Fix: Fixed sidebar — main content scrolls, sidebar does not

## Pattern (mandatory for PageLayout pages)

| Region | Classes / behavior |
|--------|-------------------|
| **Shell** | `h-dvh flex overflow-hidden` on `PageLayout` root |
| **Sidebar** | `h-dvh shrink-0 flex flex-col min-h-0`; `lg:relative` (desktop), `fixed` (mobile drawer) |
| **Sidebar nav** | `pyx-scroll-dark overflow-y-auto` — gold thumb (see [[fixes/pyx-scrollbars]]) |
| **Main** | `overflow-y-auto` — light thumb auto-themed |
| **Main column** | `flex-1 flex flex-col min-h-0 overflow-hidden` |
| **Header** | `shrink-0` (not document-sticky — column is already fixed) |
| **Main** | `flex-1 min-h-0 overflow-y-auto overscroll-contain` — **only** page body scrolls |

## Do not

- `min-h-screen` on `PageLayout` shell (grows with content → whole page including sidebar scrolls)
- `document.body` scroll for exec/inventory/CSR pages wrapped in `PageLayout`
- Duplicate full-page scroll on page children unless intentional inner panel (tables, modals)

## Exempt

- **Login** — standalone full-page form
- **POS** — full-screen terminal layout (`posMotion`, no `PageLayout`)

## Live reference

`PageLayout.tsx` · `Sidebar.tsx`

## Links

[[005_SPA_UX_Patterns#Fixed sidebar shell (mandatory)]] · [[004_SPA_Design_System#Page shell]]
