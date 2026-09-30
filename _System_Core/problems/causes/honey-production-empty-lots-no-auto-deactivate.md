---
date: 2026-09-30
type: cause
---

# Cause: No timer for empty production lot deactivation

`is_active` was only toggled by executive Edit. Lots that hit 0 or went negative stayed active indefinitely, so the Production variant expand kept showing every spent supplier shell.
