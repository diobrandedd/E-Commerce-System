---
date: 2026-10-01
type: cause
---

# Cause: No soft-void archive for empty production lots

`is_active` only dimmed empty lots; they remained in `production_variant_list`. There was no `voided_at` path to hide spent supplier shells from the main expand list.
