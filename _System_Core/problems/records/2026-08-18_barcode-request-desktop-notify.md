---
date: 2026-08-18
type: problem-record
status: fixed
area: inventory-barcodes
tags: [problem]
---

# Problem: No Windows ping when barcodes are requested

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Staff who generate/print barcodes only saw a red sidebar count. If Pyx was minimized or they were in another app, they did not know a bottle or pouch/jar request had arrived.

## Cause

→ [[causes/barcode-request-desktop-notify]]

## Fix

→ [[fixes/barcode-request-desktop-notify]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `app/src/app/components/notifications/BarcodePendingContext.tsx` |
| Related | `app/src/app/lib/desktopBarcodeAlerts.ts`, `barcodePendingCounts.ts` |
| SPA | `PageLayout.tsx` banner + `pyx:navigate`; `AccountSettingsPage.tsx` toggle; `Sidebar.tsx` shares pending counts |
| Fragile | [[003_Fragile_Edges#Desktop barcode request notifications]] |

## Deploy

- `cd app && npm run build` + hard refresh (SPA only)

## Links

- **Fragile edges:** [[003_Fragile_Edges#Desktop barcode request notifications]]
- **Related:** [[records/2026-08-18_sidebar-barcode-request-badges]]

## AI rules (optional)

Do not toast on first poll (baseline only). Keep Pyx open for Windows notifications; no server-side push.
