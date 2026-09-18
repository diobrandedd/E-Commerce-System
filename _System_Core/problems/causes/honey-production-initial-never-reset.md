---
date: 2026-09-18
type: cause
area: honey-production
tags: [cause]
---

# Cause: Production initial_qty only ever increased

`initial_qty` was set on warehouse→production stock-out and increased on refill, but never cleared when `current_qty` hit zero. Emptied lots kept the old cycle max in the progress UI (“0% of 205 gal”).
