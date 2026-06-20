---
date: 2026-06-05
type: problem-record
status: fixed
area: spa-ui
tags: [problem, spa, modals, enhancement]
---

# Problem: Modal dialogs lacked a single standard shell

## Symptom

Center dialogs varied in backdrop, elevation, and motion across pages.

## Cause

→ [[causes/inline-modal-overlays-inconsistent]]

## Fix

→ [[fixes/system-wide-app-modal-shell]]

## Files

| Role | Path |
|------|------|
| Shell | `app/src/app/components/AppModal.tsx` |
| Rule | `.cursor/rules/app-modal.mdc` |

## Deploy

- SPA build + hard refresh

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Modal patterns — AppModal is the standard]]
