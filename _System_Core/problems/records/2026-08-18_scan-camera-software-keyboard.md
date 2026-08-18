---
date: 2026-08-18
type: problem-record
status: fixed
area: scan-camera
tags: [problem]
---

# Problem: Phone keyboard opens during camera scan

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

On Android Chrome, opening any barcode camera (stock-in, pack, POS, leak/damage, verify) also opened Gboard. After each successful scan the keyboard came back, covering the viewfinder.

## Cause

→ [[causes/scan-camera-software-keyboard]]

## Fix

→ [[fixes/scan-camera-software-keyboard]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `app/src/app/lib/scanWedgeField.ts` |
| SPA | `BarcodeCameraScannerModal.tsx`, `TrackingQrScannerModal.tsx` |
| Callers | Honey/dept scan panels, pack scan, POS, leak/damage, verify |
| Fragile | [[003_Fragile_Edges#Scan fields vs phone software keyboard]] |

## Deploy

- `cd app && npm run build` + hard refresh (SPA only)

## Links

- **Fragile edges:** [[003_Fragile_Edges#Scan fields vs phone software keyboard]]

## AI rules (optional)

Do not `.focus()` a text scan field while a camera modal is open. Use `useScanWedgeField`.
