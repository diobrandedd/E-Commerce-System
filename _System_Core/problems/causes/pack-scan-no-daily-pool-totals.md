---
date: 2026-09-07
type: cause
area: deliveries
---

# Cause: Pack scan had no daily pool totals

Pack sessions stored item `pool` (packaging / rts_sellable / finished) but the live Pack scan UI only showed the open session count. There was no per-user, calendar-day rollup across confirmed sessions and the open draft.
