---
date: 2026-09-16
type: cause
status: documented
area: honey-production
tags: [cause]
---

# Cause: No production-lot active flag / scan filter

`honey_production_rows` had no `is_active` column. Production scan UI passed the full `production_list` into the lot `<select>`, including zero-gallon and stale rows. Executive edit modal supported warehouse/packaging but Production page never mounted it for gallon/active edits.
