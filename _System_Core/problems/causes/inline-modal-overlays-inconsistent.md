---
type: cause
tags: [cause, spa, modals]
---

# Cause: Inconsistent inline modal overlays across SPA

## What went wrong

Center dialogs used mixed patterns — inline `fixed inset-0`, Radix Dialog, custom overlays — causing inconsistent backdrop, motion, and z-index behavior.

## Related problems

- [[records/2026-06-05_app-modal-standard]]

## Prevent

Use `AppModal` shell for all new center modals per `.cursor/rules/app-modal.mdc`.
